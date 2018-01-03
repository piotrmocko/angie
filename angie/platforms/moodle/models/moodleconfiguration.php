<?php
/**
 * @package   angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author    Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieModelMoodleConfiguration extends AngieModelBaseConfiguration
{
	public function __construct($config = array(), AContainer $container = null)
	{
		// Call the parent constructor
		parent::__construct($config, $container);

		// Get the Moodle version from the configuration or the session
		if (array_key_exists('version', $config))
		{
			$moodleVersion = $config['version'];
		}
		else
		{
			$moodleVersion = $this->container->session->get('version', '2.0.0');
		}

		// Load the configuration variables from the session or the default configuration shipped with ANGIE
		$this->configvars = $this->container->session->get('configuration.variables');

		if (empty($this->configvars))
		{
			$this->configvars = $this->getDefaultConfig();
			$realConfig = $this->loadFromFile(APATH_CONFIGURATION . '/config.php');
			$this->configvars = array_merge($this->configvars, $realConfig);

			if (!empty($this->configvars))
			{
				$this->saveToSession();
			}
		}
	}

	/**
	 * Returns an associative array with default settings
	 *
	 * @return array
	 */
	public function getDefaultConfig()
	{
		// MySQL settings
		$config['dbtype']  = 'mysqli';
		$config['dbhost']  = '';
		$config['dbname']  = '';
		$config['dbuser']  = '';
		$config['dbpass']  = '';
		$config['dbprefix']  = '';

		// Other
		$config['wwwroot']  = '';
		$config['dataroot'] = '';
		$config['admin']    = '';
		$config['directorypermissions'] = '';

		return $config;
	}

	/**
	 * Loads the configuration information from a PHP file
	 *
	 * @param   string $file The full path to the file
	 *
	 * @return array
	 */
	public function loadFromFile($file)
	{
		$config          = array();

		// Sadly Moodle configuration file is a simple PHP file, that will load the whole library, so we can't just include it
		// The only option is to parse each line and extract the value
		$contents = file_get_contents($file);

		//Ok, now let's start analyzing
		$lines = explode("\n", $contents);

		foreach($lines as $line)
		{
			$line = trim($line);

			// Search for defines
			if (strpos($line, '$CFG->') === 0)
			{
				$line = trim(substr($line, 6));
				$line = trim(rtrim($line, ';'));
				list($key, $value) = explode('=', $line);
				$key = trim($key);
				$key = trim($key, "'\"");
				$value = trim($value);
				$value = trim($value, "'\"");

				switch (strtolower($key))
				{
					case 'dbname':
						$config['dbname'] = $value;
						break;

					case 'dbuser':
						$config['dbuser'] = $value;
						break;

					case 'dbpass':
						$config['dbpass'] = $value;
						break;

					case 'dbhost':
						$config['dbhost'] = $value;
						break;

					case 'prefix':
						$config['dbprefix'] = $value;
						break;

					case 'wwwroot':
						$config['wwwroot'] = $value;
						break;

					case 'dataroot':
						$config['dataroot'] = $value;
						break;

					case 'admin':
						$config['admin'] = $value;
						break;

					case 'directorypermissions':
						$config['directorypermissions'] = $value;
						break;

				}
			}
		}

		// Moodle has some options set inside the db, too
		/** @var AngieModelDatabase $model */
		$model		 = AModel::getAnInstance('Database', 'AngieModel', array(), $this->container);
		$keys		 = $model->getDatabaseNames();
		$firstDbKey	 = array_shift($keys);

		$connectionVars = $model->getDatabaseInfo($firstDbKey);

		try
		{
			$name = $connectionVars->dbtype;
			$options = array(
				'database'	 => $connectionVars->dbname,
				'select'	 => 1,
				'host'		 => $connectionVars->dbhost,
				'user'		 => $connectionVars->dbuser,
				'password'	 => $connectionVars->dbpass,
				'prefix'	 => $connectionVars->prefix
			);

			$db = ADatabaseFactory::getInstance()->getDriver($name, $options);

			// The site name (full and short) is stored in the courses table. We can address it using category = 0
			$query = $db->getQuery(true)
						->select(array($db->qn('fullname'), $db->qn('shortname')))
						->from('#__course')
						->where($db->qn('category').' = '.$db->q(0));

			$row = $db->setQuery($query)->loadObject();

			$config['fullname']  = $row->fullname;
			$config['shortname'] = $row->shortname;
		}
		catch (Exception $exc)
		{

		}

		return $config;
	}

	/**
	 * Creates the string that will be put inside the new configuration file.
	 * This is a separate function so we can show the content if we're unable to write to the filesystem
	 * and ask the user to manually do that.
	 */
	public function getFileContents($file = null)
	{
		if(!$file)
		{
			$file = APATH_ROOT.'/config.php';
		}

		$new_config = '';
		$old_config = file_get_contents($file);

		$lines = explode("\n", $old_config);

		foreach($lines as $line)
		{
			$line    = trim($line);
			$matches = array();

			if(strpos($line, '$CFG->') !== false)
			{
				preg_match('#\$CFG\->(.*?)\s?=#', $line, $matches);

				if(isset($matches[1]))
				{
					$key = trim($matches[1]);

					switch(strtolower($key))
					{
						case 'dbtype' :
							$value = $this->get('dbtype');
							$line = '$CFG->'.$key." = '".$value."';";
							break;
						case 'dbhost':
							$value = $this->get('dbhost');
							$line = '$CFG->'.$key." = '".$value."';";
							break;
						case 'dbname':
							$value = $this->get('dbname');
							$line = '$CFG->'.$key." = '".$value."';";
							break;
						case 'dbuser':
							$value = $this->get('dbuser');
							$line = '$CFG->'.$key." = '".$value."';";
							break;
						case 'dbpass':
							$value = $this->get('dbpass');
							$line = '$CFG->'.$key." = '".$value."';";
							break;
						case 'prefix':
							$value = $this->get('dbprefix');
							$line = '$CFG->'.$key." = '".$value."';";
							break;
						case 'wwwroot':
							$value = $this->get('wwwroot');
							$line = '$CFG->'.$key." = '".$value."';";
							break;
						case 'dataroot':
							$value = $this->container->session->get('directories.moodledata', $this->get('dataroot'));
							$line = '$CFG->'.$key." = '".$value."';";
							break;
						case 'admin':
							$value = $this->get('admin');
							$line = '$CFG->'.$key." = '".$value."';";
							break;
						case 'directorypermissions':
							$value = $this->get('directorypermissions');
							$line = '$CFG->'.$key." = ".$value.';';
							break;

						// We should not touch dblibrary variable
						default:
							// Do nothing, it's a variable we're not insterested in
							break;
					}
				}
			}

			$new_config .= $line."\n";
		}

		return $new_config;
	}

	/**
	 * Writes the new config params inside the config.php file and the database.
	 *
	 * @param   string  $file
	 *
	 * @return bool
	 */
	public function writeConfig($file)
	{
		// First of all I'll save the options stored inside the db. In this way, even if
		// the configuration file write fails, the user has only to manually update the
		// config file and he's ready to go.

        /** @var AngieModelDatabase $model */
		$model		 = AModel::getAnInstance('Database', 'AngieModel', array(), $this->container);
		$keys		 = $model->getDatabaseNames();
		$firstDbKey	 = array_shift($keys);

		$connectionVars = $model->getDatabaseInfo($firstDbKey);

		$name = $connectionVars->dbtype;
		$options = array(
			'database'	 => $connectionVars->dbname,
			'select'	 => 1,
			'host'		 => $connectionVars->dbhost,
			'user'		 => $connectionVars->dbuser,
			'password'	 => $connectionVars->dbpass,
			'prefix'	 => $connectionVars->prefix
		);

		$db = ADatabaseFactory::getInstance()->getDriver($name, $options);

		/*
		 * We have to update the following values: Fullname and short name, chat host and ip,
		 * create a new exportsalt for calendars, create a new site identifier
		 */

		// Update site fullname and shortname
		$query = $db->getQuery(true)
					->update($db->qn('#__course'))
					->set($db->qn('fullname').' = '.$db->q($this->get('fullname')))
					->set($db->qn('shortname').' = '.$db->q($this->get('shortname')))
					->where($db->qn('category').' = '.$db->q(0));
		$db->setQuery($query)->execute();

		// Create a new site identifier
		$newsiteidentifier = $this->random_string(32).$_SERVER['HTTP_HOST'];
		$query = $db->getQuery(true)
					->update($db->qn('#__config'))
					->set($db->qn('value').' = '.$db->q($newsiteidentifier))
					->where($db->qn('name').' = '.$db->q('siteidentifier'));
		$db->setQuery($query)->execute();

		// Create a new export calendar salt
		$new_salt = $this->random_string(60);
		$query = $db->getQuery(true)
					->update($db->qn('#__config'))
					->set($db->qn('value').' = '.$db->q($new_salt))
					->where($db->qn('name').' = '.$db->q('calendar_exportsalt'));
		$db->setQuery($query)->execute();

		// Update the chat server host
		$query = $db->getQuery(true)
					->update($db->qn('#__config'))
					->set($db->qn('value').' = '.$db->q($this->get('chat_host')))
					->where($db->qn('name').' = '.$db->q('chat_serverhost'));
		$db->setQuery($query)->execute();

		// Update the chat server ip
		$query = $db->getQuery(true)
					->update($db->qn('#__config'))
					->set($db->qn('value').' = '.$db->q($this->get('chat_ip')))
					->where($db->qn('name').' = '.$db->q('chat_serverip'));
		$db->setQuery($query)->execute();

		$new_config = $this->getFileContents($file);

		if(!file_put_contents($file, $new_config))
		{
			return false;
		}

		return true;
	}

	/**
	 * Random function copied from Moodle's libraries
	 *
	 * @param int $length
	 * @return string
	 */
	public function random_string ($length = 15)
	{
		$pool  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$pool .= 'abcdefghijklmnopqrstuvwxyz';
		$pool .= '0123456789';
		$poollen = strlen($pool);
		$string = '';

		for ($i = 0; $i < $length; $i++)
		{
			$string .= substr($pool, (mt_rand()%($poollen)), 1);
		}

		return $string;
	}
}
