<?php
/**
 * @package   angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author    Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

abstract class AngieModelBaseReplacedata extends AModel
{
	/** @var ADatabaseDriver Reference to the database driver object */
	protected $db = null;

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
			$model      = AModel::getAnInstance('Database', 'AngieModel', array(), $this->container);
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
		$session      = $this->container->session;
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
	 * Returns all the database tables which are not part of the Platform core
	 *
	 * @return array
	 */
	abstract public function getNonCoreTables();

	/**
	 * Loads the engine status off the session
	 */
	public function loadEngineStatus()
	{
		$session = $this->container->session;

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
		$session = $this->container->session;

		$session->set('replacedata.tables', $this->tables);
		$session->set('replacedata.currentTable', $this->currentTable);
		$session->set('replacedata.currentRow', $this->currentRow);
		$session->set('replacedata.totalRows', $this->totalRows);
		$session->set('replacedata.batchSize', $this->batchSize);
		$session->set('replacedata.max_exec', $this->max_exec);
	}

	/**
	 * Initialises the replacement engine
	 * Since every platform could gave a different init logic, it should be implemented
	 * in descendant classes
	 */
	abstract public function initEngine();

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
		$serialisedHelper = new AUtilsSerialised();

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

			// Is this a simple replacement (one SQL query)?
			if ($this->currentTable['method'] == 'simple')
			{
				$msg = $this->currentTable['table'];

				// Perform the replacement
				$this->performSimpleReplacement($db);

				// Go to the next table
				$this->currentTable = null;

				continue;
			}

			// If we're done processing this table, go to the next table
			if ($this->currentRow >= $this->totalRows)
			{
				$msg = $this->currentTable['table'];

				$this->currentTable = null;

				continue;
			}

			// This is a complex replacement for serialised data. Let's get a bunch of data.
			$tableName        = $this->currentTable['table'];
			$this->currentRow = empty($this->currentRow) ? 0 : $this->currentRow;
			try
			{
				$query = $db->getQuery(true)->select('*')->from($db->qn($tableName));
				$data  = $db->setQuery($query, $this->currentRow, $this->batchSize)->loadAssocList();
			}
			catch (Exception $e)
			{
				// If the table does not exist go to the next table
				$this->currentTable = null;

				continue;
			}

			if ( !empty($data))
			{
				foreach ($data as $row)
				{
					// Make sure we have time
					if ($this->timer->getTimeLeft() <= 0)
					{
						$msg = $this->currentTable['table'] . ' ' . $this->currentRow . ' / ' . $this->totalRows;

						break;
					}

					// Which fields should I parse?
					if ( !empty($this->currentTable['fields']))
					{
						$fields = $this->currentTable['fields'];
					}
					else
					{
						$fields = array_keys($row);
					}

					foreach ($fields as $field)
					{
						$fieldValue = $row[$field];
						$from       = array_keys($this->replacements);

						if ($serialisedHelper->isSerialised($fieldValue))
						{
							// Replace serialised data
							try
							{
								$decoded = $serialisedHelper->decode($fieldValue);

								$serialisedHelper->replaceTextInDecoded($decoded, $from, $this->replacements);

								$fieldValue = $serialisedHelper->encode($decoded);
							}
							catch (Exception $e)
							{
								// Yeah, well...
							}
						}
						else
						{
							// Replace text data
							$fieldValue = str_replace($from, $this->replacements, $fieldValue);
						}

						$row[$field] = $fieldValue;
					}

					$row = array_map(array($db, 'quote'), $row);

					$query = $db->getQuery(true)->replace($db->qn($tableName))
						->columns(array_keys($row))
						->values(implode(',', $row));

					try
					{
						$db->setQuery($query)->execute();
					}
					catch (Exception $e)
					{
						// If there's no primary key the replacement will fail. Oh, well, what the hell...
					}

					$this->currentRow++;
				}
			}
		}

		return array('msg' => $msg, 'more' => $more);
	}

	/**
	 * Returns the default replacement values
	 *
	 * @return array
	 */
	abstract protected function getDefaultReplacements();

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
	 * Removes the subdomain from a full domain name. For example:
	 * removeSubdomain('www.example.com') = 'example.com'
	 * removeSubdomain('example.com') = 'example.com'
	 * removeSubdomain('localhost.localdomain') = 'localhost.localdomain'
	 * removeSubdomain('foobar.localhost.localdomain') = 'localhost.localdomain'
	 * removeSubdomain('localhost') = 'localhost'
	 *
	 * @param   string  $domain  The domain to remove its subdomain
	 *
	 * @return  string
	 */
	protected function removeSubdomain($domain)
	{
		$domain = trim($domain, '.');

		$parts = explode('.', $domain);

		if (count($parts) > 2)
		{
			array_shift($parts);
		}

		return implode('.', $parts);
	}
}