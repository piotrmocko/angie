<?php
/**
 * @package   angi4j
 * @copyright Copyright (C) 2009-2017 Nicholas K. Dionysopoulos. All rights reserved.
 * @author    Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieModelWordpressConfiguration extends AngieModelBaseConfiguration
{
	public function __construct($config = array(), AContainer $container = null)
	{
		// Call the parent constructor
		parent::__construct($config, $container);

		// Load the configuration variables from the session or the default configuration shipped with ANGIE
		$this->configvars = $this->container->session->get('configuration.variables');

		if (empty($this->configvars) || empty($this->configvars['blogname']))
		{
			$this->configvars = $this->getDefaultConfig();
			$realConfig       = array();

            if (empty($this->configvars['blogname']))
			{
				$realConfig = $this->loadFromFile(APATH_CONFIGURATION . '/wp-config.php');
                $this->getOptionsFromDatabase($realConfig);
			}

			$this->configvars = array_merge($this->configvars, $realConfig);

			if ( !empty($this->configvars))
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
		$config['dbname']    = '';
		$config['dbuser']    = '';
		$config['dbpass']    = '';
		$config['dbhost']    = '';
		$config['dbcharset'] = '';
		$config['dbcollate'] = '';
		$config['dbprefix']  = '';

		// Other
		$config['blogname'] = '';

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

		// Sadly WordPress configuration file is a simple PHP file, where people can (and will!) modify it
		// so we can't just include it because we could have "funny" surprise
		// The only option is to parse each line and extract the value
		$contents = file_get_contents($file);

		// First of all let's remove any comments
		// This will strip everything, even comments after assigment, like
		//
		// $foo = 'bar' #same line comment
		//
		// This will invalidate the Authentication Keys and Salt part, but that's not a problem
		// since we will have to change them
		// reference: http://stackoverflow.com/a/13114141/485241
		$contents = preg_replace('~(?:#|//)[^\r\n]*|/\*.*?\*/~s', '', $contents);

		//Ok, now let's start analyzing
		$lines = explode("\n", $contents);

		foreach ($lines as $line)
		{
			$line = trim($line);

			// Search for defines
			if (strpos($line, 'define') === 0)
			{
				$line = substr($line, 6);
				$line = trim($line);
				$line = rtrim($line, ';');
				$line = trim($line);
				$line = trim($line, '()');
				list($key, $value) = explode(',', $line);
				$key   = trim($key);
				$key   = trim($key, "'\"");
				$value = trim($value);
				$value = trim($value, "'\"");

				switch (strtoupper($key))
				{
					case 'DB_NAME':
						$config['dbname'] = $value;
						break;

					case 'DB_USER':
						$config['dbuser'] = $value;
						break;

					case 'DB_PASSWORD':
						$config['dbpass'] = $value;
						break;

					case 'DB_HOST':
						$config['dbhost'] = $value;
						break;

					case 'DB_CHARSET':
						$config['dbcharset'] = $value;
						break;

					case 'DB_COLLATE':
						$config['dbcollate'] = $value;
						break;

					case 'DOMAIN_CURRENT_SITE':
						$config['domain_current_site'] = $value;
						break;

					case 'PATH_CURRENT_SITE':
						$config['path_current_site'] = $value;
						break;

					case 'SITE_ID_CURRENT_SITE':
						$config['site_id_current_site'] = $value;
						break;

					case 'BLOG_ID_CURRENT_SITE':
						$config['blog_id_current_site'] = $value;
						break;

					case 'MULTISITE':
						switch (strtoupper($value))
						{
							case 'FALSE':
							case '0':
								$value = false;
								break;

							default:
								$value = true;
								break;
						}

						$config['multisite'] = $value;
						break;

					case 'SUBDOMAIN_INSTALL':
						switch (strtoupper($value))
						{
							case 'FALSE':
							case '0':
								$value = false;
								break;

							default:
								$value = true;
								break;
						}

						$config['subdomain_install'] = $value;
						break;
				}
			}
			// Table prefix
			elseif (strpos($line, '$table_prefix') === 0)
			{
				$parts      = explode('=', $line, 2);
				$prefixData = trim($parts[1]);
				$prefixData = rtrim($prefixData, ';');
				$prefixData = trim($prefixData, "'\"");

				$config['olddbprefix'] = $prefixData;
				$config['dbprefix']    = $prefixData;
			}
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
		if ( !$file)
		{
			$file = APATH_ROOT . '/wp-config.php';
		}

		$new_config = '';
		$old_config = file_get_contents($file);

        // Check if the file is UTF encoded with BOM. We have to remove it or we will get a white page
        // Sadly several editors are setting the flag automatically; since they are not visible, the user has
        // no easy method to remove them
        $bom = pack("CCC", 0xef, 0xbb, 0xbf);

        if (strncmp($old_config, $bom, 3) === 0)
        {
            // Let's strip out any BOM char
            $old_config = substr($old_config, 3);
        }

		$lines = explode("\n", $old_config);

		foreach ($lines as $line)
		{
			$line    = trim($line);
			$matches = array();

			// Skip commented lines. However it will get the line between a multiline comment, but that's not a problem
			if (strpos($line, '#') === 0 || strpos($line, '//') === 0 || strpos($line, '/*') === 0)
			{
				// simply do nothing, we will add the line later
			}
			elseif (strpos($line, 'define(') !== false)
			{
				preg_match('#define\(\s?["\'](.*?)["\']\,#', $line, $matches);

				if (isset($matches[1]))
				{
					$key = $matches[1];

					switch (strtoupper($key))
					{
						case 'DB_NAME' :
							$value = $this->get('dbname');
							$line  = "define('" . $key . "', '" . $value . "');";
							break;

						case 'DB_USER':
							$value = $this->get('dbuser');
							$line  = "define('" . $key . "', '" . $value . "');";
							break;

						case 'DB_PASSWORD':
							$value = $this->get('dbpass');
							$value = addcslashes($value, "'\\");
							$line  = "define('" . $key . "', '" . $value . "');";
							break;

						case 'DB_HOST':
							$value = $this->get('dbhost');
							$line  = "define('" . $key . "', '" . $value . "');";
							break;

						case 'DB_CHARSET':
							$value = $this->get('dbcharset');
							$line  = "define('" . $key . "', '" . $value . "');";
							break;

						case 'DB_COLLATE':
							$value = $this->get('dbcollate', 'utf8_general_ci');
							$line  = "define('" . $key . "', '" . $value . "');";
							break;

						case 'AUTH_KEY':
							$value = $this->get('auth_key');
							$line  = "define('" . $key . "', '" . $value . "');";
							break;

						case 'SECURE_AUTH_KEY':
							$value = $this->get('secure_auth_key');
							$line  = "define('" . $key . "', '" . $value . "');";
							break;

						case 'LOGGED_IN_KEY':
							$value = $this->get('logged_in_key');
							$line  = "define('" . $key . "', '" . $value . "');";
							break;

						case 'NONCE_KEY':
							$value = $this->get('nonce_key');
							$line  = "define('" . $key . "', '" . $value . "');";
							break;

						case 'AUTH_SALT':
							$value = $this->get('auth_salt');
							$line  = "define('" . $key . "', '" . $value . "');";
							break;

						case 'SECURE_AUTH_SALT':
							$value = $this->get('secure_auth_salt');
							$line  = "define('" . $key . "', '" . $value . "');";
							break;

						case 'LOGGED_IN_SALT':
							$value = $this->get('logged_in_salt');
							$line  = "define('" . $key . "', '" . $value . "');";
							break;

						case 'NONCE_SALT':
							$value = $this->get('nonce_salt');
							$line  = "define('" . $key . "', '" . $value . "');";
							break;

						// Multisite variable - Main site's domain
						case 'DOMAIN_CURRENT_SITE':
							$new_url   = $this->get('homeurl');
							$newUri    = new AUri($new_url);
							$newDomain = $newUri->getHost();
							$line      = "define('" . $key . "', '" . $newDomain . "');";
							break;

						// Multisite variable - Main site's path
						case 'PATH_CURRENT_SITE':
							$new_url = $this->get('homeurl');
							$newUri  = new AUri($new_url);
							$newPath = $newUri->getPath();
							$newPath = trim($newPath, '/');
							$newPath = empty($newPath) ? '/' : '/' . $newPath . '/';
							$line    = "define('" . $key . "', '" . $newPath . "');";
							break;

						case 'WP_HOME':
							$line      = "define('" . $key . "', '" . $this->get('homeurl') . "');";
							break;

						case 'WP_SITEURL':
							$line      = "define('" . $key . "', '" . $this->get('siteurl') . "');";
							break;

						// I think users shouldn't change the WPLANG define, since they will have
						// to add several files, it's not automatic
						default:
							// Do nothing, it's a variable we're not interested in
							break;
					}
				}
			}
			elseif (strpos($line, '$table_prefix') === 0)
			{
				$line = '$table_prefix = ' . "'" . $this->get('dbprefix') . "';";
			}

			$new_config .= $line . "\n";
		}

		return $new_config;
	}

	/**
	 * Writes the new config params inside the wp-config.php file and the database.
	 *
	 * @param   string $file
	 *
	 * @return bool
	 */
	public function writeConfig($file)
	{
		$new_config = $this->getFileContents($file);

		if ( !file_put_contents($file, $new_config))
		{
			return false;
		}

		return true;
	}

	/**
	 * @param $config
	 */
	protected function getOptionsFromDatabase(&$config)
	{
        // Wordpress has some options set inside the db, too
        /** @var AngieModelDatabase $model */
		$model      = AModel::getAnInstance('Database', 'AngieModel', array(), $this->container);
		$keys       = $model->getDatabaseNames();
		$firstDbKey = array_shift($keys);

		$connectionVars = $model->getDatabaseInfo($firstDbKey);

		try
		{
			$name    = $connectionVars->dbtype;
			$options = array(
				'database' => $connectionVars->dbname,
				'select'   => 1,
				'host'     => $connectionVars->dbhost,
				'user'     => $connectionVars->dbuser,
				'password' => $connectionVars->dbpass,
				'prefix'   => $connectionVars->prefix
			);

			$db = ADatabaseFactory::getInstance()->getDriver($name, $options);

			$searchFor = array(
				$db->q('blogname'),
				$db->q('blogdescription'),
				$db->q('home'),
				$db->q('siteurl')
			);

			$query      = $db->getQuery(true)
							 ->select(array($db->qn('option_name'), $db->qn('option_value')))
							 ->from('#__options')
							 ->where($db->qn('option_name') . ' IN (' . implode(',', $searchFor) . ')');
			$wp_options = $db->setQuery($query)->loadObjectList();

			foreach ($wp_options as $option)
			{
				// Let me save the old home url, it will useful later when I'll have to replace it inside the posts
				if ($option->option_name == 'home')
				{
					$config['oldurl'] = $option->option_value;
				}
				else
				{
					$config[$option->option_name] = $option->option_value;
				}
			}
		}
		catch (Exception $exc)
		{
		}
	}
}