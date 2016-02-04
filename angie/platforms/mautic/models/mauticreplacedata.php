<?php
/**
 * @package   angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author    Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieModelMauticReplacedata extends AModel
{
	/** @var ADatabaseDriver Reference to the database driver object */
	private $db = null;

	/** @var array The tables we have to work on: (table, method, fields) */
	protected $tables = array();

	/** @var string The current table being processed */
	protected $currentTable = null;

	/** @var int The current row being processed */
	protected $currentRow = null;

	/** @var int The total rows in the table being processed */
	protected $totalRows = null;

	/** @var array The replacements to conduct */
	protected $replacements = array();

	/** @var int How many rows to process at once */
	protected $batchSize = 100;

	/** @var null|ATimer The timer used to step the engine */
	protected $timer = null;

	/** @var int Maximum execution time (in seconds) */
	protected $max_exec = 3;

	/**
	 * Get a reference to the database driver object
	 *
	 * @return ADatabaseDriver
	 */
	public function &getDbo()
	{
		if ( !is_object($this->db))
		{
			/** @var AngieModelDatabase $model */
			$model      = AModel::getAnInstance('Database', 'AngieModel');
			$keys       = $model->getDatabaseNames();
			$firstDbKey = array_shift($keys);

			$connectionVars = $model->getDatabaseInfo($firstDbKey);
			$name           = $connectionVars->dbtype;

			$options = array(
				'database' => $connectionVars->dbname,
				'select'   => 1,
				'host'     => $connectionVars->dbhost,
				'user'     => $connectionVars->dbuser,
				'password' => $connectionVars->dbpass,
				'prefix'   => $connectionVars->prefix,
			);

			$this->db = ADatabaseFactory::getInstance()->getDriver($name, $options);
			$this->db->setUTF();
		}

		return $this->db;
	}

	/**
	 * Get the data replacement values
	 *
	 * @param bool $fromRequest Should I override session data with those from the request?
	 *
	 * @return array
	 */
	public function getReplacements($fromRequest = false)
	{
		$session      = ASession::getInstance();
		$replacements = $session->get('dataReplacements', array());

		if (empty($replacements))
		{
			$replacements = array();
		}

		if ($fromRequest)
		{
			$replacements = array();

			$keys   = trim($this->input->get('replaceFrom', '', 'string', 2));
			$values = trim($this->input->get('replaceTo', '', 'string', 2));

			if ( !empty($keys))
			{
                $keys   = explode("\n", $keys);
                $values = explode("\n", $values);

				foreach ($keys as $k => $v)
				{
					if ( !isset($values[$k]))
					{
						continue;
					}

					$replacements[$v] = $values[$k];
				}
			}
		}

		if (empty($replacements))
		{
			$replacements = $this->getDefaultReplacements();
		}

		$session->set('dataReplacements', $replacements);

		return $replacements;
	}

	/**
	 * Returns all the database tables which are not part of the WordPress core
	 *
	 * @return array
	 */
	public function getNonCoreTables()
	{
		// Get a list of core tables
		$coreTables = array(
			'#__commentmeta', '#__comments', '#__links', '#__options', '#__postmeta', '#__posts',
			'#__term_relationships', '#__term_taxonomy', '#__terms', '#__usermeta', '#__users',
		);

		$db = $this->getDbo();

		// Now get a list of non-core tables
		$prefix       = $db->getPrefix();
		$prefixLength = strlen($prefix);
		$allTables    = $db->getTableList();

		$result = array();

		foreach ($allTables as $table)
		{
			if (substr($table, 0, $prefixLength) == $prefix)
			{
				$table = '#__' . substr($table, $prefixLength);
			}

			if (in_array($table, $coreTables))
			{
				continue;
			}

			$result[] = $table;
		}

		return $result;
	}

	/**
	 * Loads the engine status off the session
	 */
	public function loadEngineStatus()
	{
		$session = ASession::getInstance();

		$this->replacements = $this->getReplacements();
		$this->tables       = $session->get('replacedata.tables', array());
		$this->currentTable = $session->get('replacedata.currentTable', null);
		$this->currentRow   = $session->get('replacedata.currentRow', 0);
		$this->totalRows    = $session->get('replacedata.totalRows', null);
		$this->batchSize	= $session->get('replacedata.batchSize', 100);
		$this->max_exec		= $session->get('replacedata.max_exec', 3);
	}

	/**
	 * Saves the engine status to the session
	 */
	public function saveEngineStatus()
	{
		$session = ASession::getInstance();

		$session->set('replacedata.tables', $this->tables);
		$session->set('replacedata.currentTable', $this->currentTable);
		$session->set('replacedata.currentRow', $this->currentRow);
		$session->set('replacedata.totalRows', $this->totalRows);
		$session->set('replacedata.batchSize', $this->batchSize);
		$session->set('replacedata.max_exec', $this->max_exec);
	}

	/**
	 * Initialises the replacement engine
	 */
	public function initEngine()
	{
		// Get the replacements to be made
		$this->replacements = $this->getReplacements(true);

		// Add the default core tables
		$this->tables = array(
			array(
				'table'  => '#__forms',
				'method' => 'simple', 'fields' => array('cached_html')
			),
			array(
				'table'  => '#__pages',
				'method' => 'simple', 'fields' => array('custom_html'),
			),
		);

		// Get any additional tables
		$extraTables = $this->input->get('extraTables', array(), 'array');

		if ( !empty($extraTables) && is_array($extraTables))
		{
			foreach ($extraTables as $table)
			{
				$this->tables[] = array('table' => $table, 'method' => 'serialised', 'fields' => null);
			}
		}

		// Intialise the engine state
		$this->currentTable = null;
		$this->currentRow   = null;
		$this->fields       = null;
		$this->totalRows    = null;
		$this->batchSize	= $this->input->getInt('batchSize', 100);
		$this->max_exec		= $this->input->getInt('max_exec', 3);

		// Replace keys in #__options which depend on the database table prefix, if the prefix has been changed
		$this->timer = new ATimer($this->max_exec, 75);

		// Finally, return and let the replacement engine run
		return array('msg' => AText::_('SETUP_LBL_REPLACEDATA_MSG_INITIALISED'), 'more' => true);
	}

	/**
	 * Performs a single step of the data replacement engine
	 *
	 * @return  array  Status of the engine (msg: error message, more: true if I need more steps)
	 */
	public function stepEngine()
	{
		if ( !is_object($this->timer) || !($this->timer instanceof ATimer))
		{
			$this->timer = new ATimer($this->max_exec, 75);
		}

		$msg              = '';
		$more             = true;
		$db               = $this->getDbo();

		while ($this->timer->getTimeLeft() > 0)
		{
			// Are we done with all tables?
			if (is_null($this->currentTable) && empty($this->tables))
			{
				$msg  = AText::_('SETUP_LBL_REPLACEDATA_MSG_DONE');
				$more = false;

				break;
			}

			// Last table done and ready for more?
			if (is_null($this->currentTable))
			{
				$this->currentTable = array_shift($this->tables);
				$this->currentRow   = 0;

				if (empty($this->currentTable['table']))
				{
					$msg  = AText::_('SETUP_LBL_REPLACEDATA_MSG_DONE');
					$more = false;

					break;
				}

				$query = $db->getQuery(true)
							->select('COUNT(*)')->from($db->qn($this->currentTable['table']));

				try
				{
					$this->totalRows = $db->setQuery($query)->loadResult();
				}
				catch (Exception $e)
				{
					// If the table does not exist go to the next table
					$this->currentTable = null;
					continue;
				}
			}

			// Simple replacement (one SQL query)
            $msg = $this->currentTable['table'];

            // Perform the replacement
            $this->performSimpleReplacement($db);

            // Go to the next table
            $this->currentTable = null;
		}

		return array('msg' => $msg, 'more' => $more);
	}

	/**
	 * Returns the default replacement values
	 *
	 * @return array
	 */
	protected function getDefaultReplacements()
	{
		$replacements = array();

		/** @var AngieModelConfiguration $config */
		$config = AModel::getAnInstance('Configuration', 'AngieModel');

		// Main site's URL
		$newReplacements = $this->getDefaultReplacementsForMainSite($config);
		$replacements    = array_merge($replacements, $newReplacements);

		// All done
		return $replacements;
	}

	/**
	 * Perform a simple replacement on the current table
	 *
	 * @param ADatabaseDriver $db
	 *
	 * @return void
	 */
	protected function performSimpleReplacement($db)
	{
		$tableName = $this->currentTable['table'];

		// Run all replacements
		foreach ($this->replacements as $from => $to)
		{
			$query = $db->getQuery(true)
						->update($db->qn($tableName));

			foreach ($this->currentTable['fields'] as $field)
			{
				$query->set(
					$db->qn($field) . ' = REPLACE(' .
					$db->qn($field) . ', ' . $db->q($from) . ', ' . $db->q($to) .
					')');
			}

			try
			{
				$db->setQuery($query)->execute();
			}
			catch (Exception $e)
			{
				// Do nothing if the replacement fails
			}
		}
	}

	/**
	 * Internal method to get the default replacements for the main site URL
	 *
	 * @param   AngieModelConfiguration $config The configuration model
	 *
	 * @return  array  Any replacements to add
	 */
	private function getDefaultReplacementsForMainSite($config)
	{
		$replacements = array();

		// These values are stored inside the session, after the setup step
		$old_url = $config->get('old_live_site');
		$new_url = $config->get('live_site');

		if ($old_url == $new_url)
		{
			return $replacements;
		}

		// Replace the absolute URL to the site
		$replacements[$old_url] = $new_url;

		// If the relative path to the site is different, replace it too.
		$oldUri = new AUri($old_url);
		$newUri = new AUri($new_url);

		$oldPath = $oldUri->getPath();
		$newPath = $newUri->getPath();

		if ($oldPath != $newPath)
		{
			$replacements[$oldPath] = $newPath;

			return $replacements;
		}

		return $replacements;
	}
}