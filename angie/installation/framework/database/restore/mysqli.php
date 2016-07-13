<?php
/**
 * @package   angifw
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author    Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 *
 * Akeeba Next Generation Installer Framework
 */

defined('_AKEEBA') or die();

class ADatabaseRestoreMysqli extends ADatabaseRestore
{
	/**
	 * Overloaded constructor, allows us to set up error codes and connect to
	 * the database.
	 *
	 * @param   string $dbkey       @see {ADatabaseRestore}
	 * @param   array  $dbiniValues @see {ADatabaseRestore}
	 */
	public function __construct($dbkey, $dbiniValues)
	{
		parent::__construct($dbkey, $dbiniValues);

		// Set up allowed error codes
		$this->allowedErrorCodes = array(
			1262,   // Truncated row when importing CSV (should ever occur)
			1263,   // Data truncated, NULL for NOT NULL...
			1264,   // Out of range value for column
			1265,   // "Data truncated" warning
			1266,   // Table created with MyISAM instead of InnoDB
			1287,   // Deprecated syntax
			1299,   // Invalid TIMESTAMP column value
			// , 1406	// "Data too long" error
		);

		// Set up allowed comment delimiters
		$this->comment = array(
			'#',
			'\'-- ',
			'---',
			'/*!',
		);

		// Connect to the database
		$this->getDatabase();

		// Suppress foreign key checks
		if ($this->dbiniValues['foreignkey'])
		{
			$this->executeQueryWithoutFailing('SET FOREIGN_KEY_CHECKS = 0');
		}

		// Suppress auto value on zero
		if ($this->dbiniValues['noautovalue'])
		{
			$this->executeQueryWithoutFailing('SET @@SESSION.sql_mode = \'NO_AUTO_VALUE_ON_ZERO\'');
		}
	}

	/**
	 * Overloaded method which will create the database (if it doesn't exist).
	 *
	 * @return  ADatabaseDriver
	 */
	protected function getDatabase()
	{
		if (!is_object($this->db))
		{
			$db = parent::getDatabase();

			$db->setUtf8Mb4AutoDetection($this->dbiniValues['utf8mb4']);

			try
			{
				$db->select($this->dbiniValues['dbname']);
			}
			catch (Exception $exc)
			{
				// We couldn't connect to the database. Maybe we have to create
				// it first. Let's see...
				$options = (object)array(
					'db_name' => $this->dbiniValues['dbname'],
					'db_user' => $this->dbiniValues['dbuser'],
				);
				$db->createDatabase($options, true);
				$db->select($this->dbiniValues['dbname']);
			}

			// Try to change the database collation, if requested
			if ($this->dbiniValues['utf8db'])
			{
				try
				{
					$db->alterDbCharacterSet($this->dbiniValues['dbname']);
				}
				catch (Exception $exc)
				{
					// Ignore any errors
				}
			}
		}

		return $this->db;
	}

	/**
	 * Processes and runs the query
	 *
	 * @param   string $query The query to process
	 *
	 * @return  boolean  True on success
	 */
	protected function processQueryLine($query)
	{
		$db = $this->getDatabase();

		$forceutf8     = $this->dbiniValues['utf8tables'];
		$downgradeUtf8 = $forceutf8 && (
				!$this->dbiniValues['utf8mb4']
				|| ($this->dbiniValues['utf8mb4'] && !$this->db->supportsUtf8mb4())
			);

		$changeEncoding = false;
		$useDelimiter   = false;

		// CREATE TABLE query pre-processing
		if (substr($query, 0, 12) == 'CREATE TABLE')
		{
			// If the table has a prefix, back it up (if requested). In any case, drop
			// the table. before attempting to create it.
			$tableName = $this->getCreateTableName($query);
			$this->dropOrRenameTable($tableName);
			$changeEncoding = $forceutf8;
		}
		// CREATE VIEW query pre-processing
		elseif ((substr($query, 0, 7) == 'CREATE ') && (strpos($query, ' VIEW ') !== false))
		{
			// In any case, drop the view before attempting to create it. (Views can't be renamed)
			$tableName = $this->getViewName($query);
			$this->dropView($tableName);
		}
		// CREATE PROCEDURE pre-processing
		elseif ((substr($query, 0, 7) == 'CREATE ') && (strpos($query, 'PROCEDURE ') !== false))
		{
			$entity_keyword = ' PROCEDURE ';

			// Drop the entity (it cannot be renamed)
			$entity_name = $this->getEntityName($query, $entity_keyword);
			$this->dropEntity($entity_keyword, $entity_name);
			// Instruct the engine to change the delimiter for this query to //
			$useDelimiter = true;
		}
		// CREATE FUNCTION pre-processing
		elseif ((substr($query, 0, 7) == 'CREATE ') && (strpos($query, 'FUNCTION ') !== false))
		{
			$entity_keyword = ' FUNCTION ';

			// Drop the entity (it cannot be renamed)
			$entity_name = $this->getEntityName($query, $entity_keyword);
			$this->dropEntity($entity_keyword, $entity_name);
			// Instruct the engine to change the delimiter for this query to //
			$useDelimiter = true;
		}
		// CREATE TRIGGER pre-processing
		elseif ((substr($query, 0, 7) == 'CREATE ') && (strpos($query, 'TRIGGER ') !== false))
		{
			$entity_keyword = ' TRIGGER ';

			// Drop the entity (it cannot be renamed)
			$entity_name = $this->getEntityName($query, $entity_keyword);
			$this->dropEntity($entity_keyword, $entity_name);
			// Instruct the engine to change the delimiter for this query to //
			$useDelimiter = true;
		}
		elseif (substr($query, 0, 6) == 'INSERT')
		{
			$query = $this->applyReplaceInsteadofInsert($query);
		}
		else
		{
			// Maybe a DROP statement from the extensions filter?
		}

		if (empty($query))
		{
			return true;
		}

		// If we have to downgrade UTF8MB4 to plain UTF8 we have to do it before executing the query
		if ($downgradeUtf8)
		{
			$query = $this->downgradeQueryToUtf8($query);
		}

		if ($useDelimiter)
		{
			// This doesn't work from PHP
			//$this->execute('DELIMITER //');
		}

		$this->execute($query);

		if ($useDelimiter)
		{
			// This doesn't work from PHP
			//$this->execute('DELIMITER ;');
		}

		// Do we have to forcibly apply UTF8 encoding?
		if (isset($tableName) && $changeEncoding)
		{
			$this->forciblyApplyTableEncoding($tableName);
		}

		return true;
	}

	/**
	 * Extract the table name from a CREATE TABLE command
	 *
	 * @param   string  $query  The SQL query for the CREATE TABLE
	 *
	 * @return  string
	 */
	protected function getCreateTableName($query)
	{
		// Rest of query, after CREATE TABLE
		$restOfQuery = trim(substr($query, 12, strlen($query) - 12));

		// Is there a backtick?
		if (substr($restOfQuery, 0, 1) == '`')
		{
			// There is... Good, we'll just find the matching backtick
			$pos       = strpos($restOfQuery, '`', 1);
			$tableName = substr($restOfQuery, 1, $pos - 1);
		}
		else
		{
			// If there are no backticks the table name ends in the next blank character
			$pos       = strpos($restOfQuery, ' ', 1);
			$tableName = substr($restOfQuery, 1, $pos - 1);
		}

		unset($restOfQuery);

		return $tableName;
	}

	/**
	 * Drop or rename a table (with a bak_ prefix), depending on the user options
	 *
	 * @param   string  $tableName  The table name to drop or rename
	 *
	 * @return  void
	 */
	protected function dropOrRenameTable($tableName)
	{
		$db = $this->getDatabase();

		$prefix   = $this->dbiniValues['prefix'];
		$existing = $this->dbiniValues['existing'];

		// Should I back the table up?
		if (($prefix != '') && ($existing == 'backup') && (strpos($tableName, '#__') == 0))
		{
			// It's a table with a prefix, a prefix IS specified and we are asked to back it up.
			// Start by dropping any existing backup tables
			$backupTable = str_replace('#__', 'bak_', $tableName);
			try
			{
				$db->dropTable($backupTable);

				$db->renameTable($tableName, $backupTable);
			}
			catch (Exception $exc)
			{
				// We can't rename the table. Fall-through to the final line to delete it.
			}
		}

		// Try to drop the table anyway
		$db->dropTable($tableName);
	}

	/**
	 * Extract the View name from a CREATE VIEW query
	 *
	 * @param   string  $query The SQL query
	 *
	 * @return  string
	 */
	protected function getViewName($query)
	{
		$view_pos    = strpos($query, ' VIEW ');
		$restOfQuery = trim(substr($query, $view_pos + 6)); // Rest of query, after VIEW string

		// Is there a backtick?
		if (substr($restOfQuery, 0, 1) == '`')
		{
			// There is... Good, we'll just find the matching backtick
			$pos       = strpos($restOfQuery, '`', 1);
			$tableName = substr($restOfQuery, 1, $pos - 1);
		}
		else
		{
			// Nope, let's assume the table name ends in the next blank character
			$pos       = strpos($restOfQuery, ' ', 1);
			$tableName = substr($restOfQuery, 1, $pos - 1);
		}

		unset($restOfQuery);

		return $tableName;
	}

	/**
	 * Drops a View (VIEWs cannot be renamed)
	 *
	 * @param   string  $tableName
	 *
	 * @return  void
	 */
	protected function dropView($tableName)
	{
		$db        = $this->getDatabase();
		$dropQuery = 'DROP VIEW IF EXISTS `' . $tableName . '`;';
		$db->setQuery(trim($dropQuery));
		$db->execute();
	}

	/**
	 * Extracts the name of an entity (procedure, trigger, function) from a CREATE query
	 *
	 * @param   string  $query           The SQL query
	 * @param   string  $entity_keyword  The entity type, uppercase (e.g. "PROCEDURE")
	 *
	 * @return  string
	 */
	protected function getEntityName($query, $entity_keyword)
	{
		$entity_pos  = strpos($query, $entity_keyword);
		$restOfQuery =
			trim(substr($query, $entity_pos + strlen($entity_keyword))); // Rest of query, after entity key string

		// Is there a backtick?
		if (substr($restOfQuery, 0, 1) == '`')
		{
			// There is... Good, we'll just find the matching backtick
			$pos         = strpos($restOfQuery, '`', 1);
			$entity_name = substr($restOfQuery, 1, $pos - 1);
		}
		else
		{
			// Nope, let's assume the entity name ends in the next blank character
			$pos         = strpos($restOfQuery, ' ', 1);
			$entity_name = substr($restOfQuery, 1, $pos - 1);
		}

		unset($restOfQuery);

		return $entity_name;
	}

	/**
	 * Drops an entity (procedure, trigger, function)
	 *
	 * @param   string  $entity_keyword  Entity type, e.g. "PROCEDURE"
	 * @param   string  $entity_name     Entity name
	 *
	 * @return  void
	 */
	protected function dropEntity($entity_keyword, $entity_name)
	{
		$db        = $this->getDatabase();
		$dropQuery = 'DROP' . $entity_keyword . 'IF EXISTS `' . $entity_name . '`;';
		$db->setQuery(trim($dropQuery));
		$db->execute();
	}

	/**
	 * Switches an INSERT INTO query into a REPLACE INTO query if the user has so specified
	 *
	 * @param   string  $query  The query to switch
	 *
	 * @return  string  The switched query
	 */
	protected function applyReplaceInsteadofInsert($query)
	{
		$replacesql = $this->dbiniValues['replace'];

		if ($replacesql)
		{
			// Use REPLACE instead of INSERT selected
			$query = 'REPLACE ' . substr($query, 7);

			return $query;
		}

		return $query;
	}

	/**
	 * Downgrade a query from UTF8MB4 to plain old UTF8
	 *
	 * @param   string  $query  The query to downgrade
	 *
	 * @return  string  The downgraded query
	 */
	protected function downgradeQueryToUtf8($query)
	{
		// Replace occurrences of utf8mb4 with utf8
		$query = str_ireplace('utf8mb4', 'utf8', $query);
		$query = str_ireplace('utf8mb4_unicode_ci', 'utf8_general_ci', $query);
		$query = str_ireplace('utf8mb4_', 'utf8_', $query);

		// Squash UTF8MB4 characters to "Unicode replacement character" (U+FFFD). Slow and reliable.
		$query = preg_replace('%(?:\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})%xs', '�', $query);

		return $query;
	}

	/**
	 * Forcibly apply a new table encoding (UTF8 or UTF8MB4 depending on user selections and execution environment)
	 *
	 * @param   string  $tableName  The table name to apply the encoding to
	 *
	 * @return  void
	 */
	protected function forciblyApplyTableEncoding($tableName)
	{
		$db = $this->getDatabase();

		// Get a list of columns
		$columns = $db->getTableColumns($tableName);
		$mods    = array(); // array to hold individual MODIFY COLUMN commands

		if (is_array($columns))
		{
			foreach ($columns as $field => $column)
			{
				// Make sure we are redefining only columns which do support a collation
				$col = (object) $column;

				if (empty($col->Collation))
				{
					continue;
				}

				$null    = $col->Null == 'YES' ? 'NULL' : 'NOT NULL';
				$default = is_null($col->Default) ? '' : "DEFAULT '" . $db->escape($col->Default) . "'";

				$collation = $this->db->supportsUtf8mb4() ? 'utf8mb4_unicode_ci' : 'utf8_general_ci';

				$mods[] = "MODIFY COLUMN `$field` {$col->Type} $null $default COLLATE $collation";
			}
		}

		// Begin the modification statement
		$sql = "ALTER TABLE `$tableName` ";

		// Add commands to modify columns
		if (!empty($mods))
		{
			$sql .= implode(', ', $mods) . ', ';
		}

		// Add commands to modify the table collation
		$charset   = $this->db->supportsUtf8mb4() ? 'utf8mb4' : 'utf8';
		$collation = $this->db->supportsUtf8mb4() ? 'utf8mb4_unicode_ci' : 'utf8_general_ci';
		$sql .= 'DEFAULT CHARACTER SET ' . $charset . ' COLLATE ' . $collation . ';';
		$db->setQuery($sql);

		try
		{
			$db->execute();
		}
		catch (Exception $exc)
		{
			// Don't fail if the collation could not be changed
		}
	}

	/**
	 * Execute a database query, ignoring any failures
	 *
	 * @param   string  $sql  The SQL query to execute
	 *
	 * @return  void
	 */
	protected function executeQueryWithoutFailing($sql)
	{
		$this->db->setQuery($sql);

		try
		{
			$this->db->execute();
		}
		catch (Exception $exc)
		{
			// Do nothing if that fails. Maybe we can continue with the restoration.
		}
	}
}