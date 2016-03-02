<?php
/**
 * @package   angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author    Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieModelPhpbbConfiguration extends AngieModelBaseConfiguration
{
	public function __construct($config = array(), AContainer $container = null)
	{
		// Call the parent constructor
		parent::__construct($config, $container);

		// Load the configuration variables from the session or the default configuration shipped with ANGIE
		$this->configvars = $this->container->session->get('configuration.variables');

		if (empty($this->configvars))
		{
			$this->configvars = $this->getDefaultConfig();
			$realConfig = $this->loadFromFile(APATH_SITE . '/config.php');
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
		$config['dbname'] = '';
		$config['dbuser'] = '';
		$config['dbpass'] = '';
		$config['dbhost'] = '';

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
		$config = array();

		if (!file_exists($file))
		{
			return $config;
		}

		// Config file is a simple PHP file with variables (not constants), where people MUST NOT
		// modify it, so we can include it "safetly"

		/**
		 * @var $dbname
		 * @var $dbuser
		 * @var $dbpasswd
		 * @var $dbhost
		 * @var $table_prefix
		 */
		include $file;

		$config['dbname'] = $dbname;
		$config['dbuser'] = $dbuser;
		$config['dbpass'] = $dbpasswd;
		$config['dbhost'] = $dbhost;
		$config['dbprefix'] = $table_prefix;

		return $config;
	}

	/**
	 * Creates the string that will be put inside the new configuration file.
	 * This is a separate function so we can show the content if we're unable to write to the filesystem
	 * and ask the user to manually do that.
	 */
	public function getFileContents($file = null)
	{
		if (!$file)
		{
			$file = APATH_ROOT . '/config.php';
		}

		$new_config = '';
		$old_config = file_get_contents($file);

		$lines = explode("\n", $old_config);

		foreach ($lines as $line)
		{
			$line = trim($line);
			$matches = array();

			// Skip commented lines. However it will get the line between a multiline comment, but that's not a problem
			if (strpos($line, '#') === 0 || strpos($line, '//') === 0 || strpos($line, '/*') === 0)
			{
				// simply do nothing, we will add the line later
			}
			else
			{
				preg_match('#\$(.*?)=#', $line, $matches);

				if (isset($matches[1]))
				{
					$key = trim($matches[1]);

					switch (strtolower($key))
					{
						case 'dbname' :
							$line = '$dbname = "' . $this->get('dbname') . '";';
							break;
						case 'dbuser':
							$line = '$dbuser = "' . $this->get('dbuser') . '";';
							break;
						case 'dbpasswd':
							$value = $this->get('dbpass');
							$value = addcslashes($value, "'\\");
							$line = '$dbpasswd = \'' . $value . '\';';
							break;
						case 'dbhost':
							$line = '$dbhost = "' . $this->get('dbhost') . '";';
							break;
						case 'table_prefix':
							$line = '$table_prefix = "' . $this->get('dbprefix') . '";';
							break;
						default:
							// Do nothing, it's a variable we're not insterested in
							break;
					}
				}
			}

			$new_config .= $line . "\n";
		}

		return $new_config;
	}

	/**
	 * Writes the new config params inside the config file and the database.
	 *
	 * @param   string $file
	 *
	 * @return bool
	 */
	public function writeConfig($file)
	{
		// First of all I'll save the options stored inside the db. In this way, even if
		// the configuration file write fails, the user has only to manually update the
		// config file and he's ready to go.

		$name = $this->get('dbtype');
		$options = array(
			'database' => $this->get('dbname'),
			'select'   => 1,
			'host'     => $this->get('dbhost'),
			'user'     => $this->get('dbuser'),
			'password' => $this->get('dbpass'),
			'prefix'   => $this->get('dbprefix')
		);

		$db = ADatabaseFactory::getInstance()->getDriver($name, $options);

		try
		{
			$query = $db->getQuery(true)
						->update($db->qn('#__config'))
						->set($db->qn('config_value') . ' = ' . $db->q($this->get('sitename')))
						->where($db->qn('config_name') . ' = ' . $db->q('sitename'));
			$db->setQuery($query)->execute();

			$query = $db->getQuery(true)
						->update($db->qn('#__config'))
						->set($db->qn('config_value') . ' = ' . $db->q($this->get('sitedescr')))
						->where($db->qn('config_name') . ' = ' . $db->q('site_desc'));
			$db->setQuery($query)->execute();

			$url = $this->get('siteurl');

			if (strpos($url, 'https://') !== false)
			{
				$protocol = 'https://';
			}
			else
			{
				$protocol = 'http://';
			}

			$url = str_replace($protocol, '', $url);

			list($server, $folder) = explode('/', $url, 2);

			if ($folder)
			{
				$folder = '/' . trim($folder, '/');
			}
			else
			{
				$folder = '/';
			}

			$query = $db->getQuery(true)
						->update($db->qn('#__config'))
						->set($db->qn('config_value') . ' = ' . $db->q($protocol))
						->where($db->qn('config_name') . ' = ' . $db->q('server_protocol'));
			$db->setQuery($query)->execute();

			$query = $db->getQuery(true)
						->update($db->qn('#__config'))
						->set($db->qn('config_value') . ' = ' . $db->q($server))
						->where($db->qn('config_name') . ' = ' . $db->q('server_name'));
			$db->setQuery($query)->execute();

			$query = $db->getQuery(true)
						->update($db->qn('#__config'))
						->set($db->qn('config_value') . ' = ' . $db->q($server))
						->where($db->qn('config_name') . ' = ' . $db->q('cookie_domain'));
			$db->setQuery($query)->execute();

			$query = $db->getQuery(true)
						->update($db->qn('#__config'))
						->set($db->qn('config_value') . ' = ' . $db->q($folder))
						->where($db->qn('config_name') . ' = ' . $db->q('script_path'));
			$db->setQuery($query)->execute();
		}
		catch (Exception $e)
		{
			// This should never happen...
		}

		$new_config = $this->getFileContents($file);

		if (!file_put_contents($file, $new_config))
		{
			return false;
		}

		return true;
	}
}