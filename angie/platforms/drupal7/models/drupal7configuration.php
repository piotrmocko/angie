<?php
/**
 * @package   angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author    Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

/**
 * WARNING! CRUFT HAZARD AHEAD!
 * Drupal supports multisite installation. Some options are stored inside the database and other inside the
 * settings.php file, moreover you can have multiple sites with a single database (different prefix) or a database for
 * every site. Since we to ask the user to modify such values, we have to read them all and show them all to the user
 */
class AngieModelDrupal7Configuration extends AngieModelBaseConfiguration
{
	public function __construct($config = array(), AContainer $container = null)
	{
		// Call the parent constructor
		parent::__construct($config, $container);

		// Load the configuration variables from the session or the default configuration shipped with ANGIE
	    $this->configvars = $this->container->session->get('configuration.variables');

		if (empty($this->configvars) || empty($this->configvars['default']['sitename']))
		{
            $this->configvars = $this->getDefaultConfig();
            $realConfig       = array();

            if (empty($this->configvars['default']['sitename']))
            {
                $folders = $this->getSettingsFolders();

                // For each folder, read its settings.php file
                foreach($folders as $folder)
                {
                    $realConfig[$folder] = $this->loadFromFile(APATH_ROOT . '/sites/'.$folder.'/settings.php');
                }

                // Let's assign these partial info to the class, so I can use them later
                $this->configvars = array_merge($this->configvars, $realConfig);

                // Ok I got all the database configuration, let's detect if I have a monodb or a multidb environment
                $master  = $realConfig['default'];
                $multidb = false;

                foreach($realConfig as $folder => $settings)
                {
                    if($folder == 'default')
                    {
                        continue;
                    }

                    // If the host or the db name is different, this is a multidb environment
                    if(($settings['dbhost'] != $master['dbhost']) || $settings['dbname'] != $master['dbname'] )
                    {
                        $multidb = true;
                        break;
                    }
                }

                $this->configvars['multidb'] = $multidb;

                // Great, now that I know how the whole database is structured, I can actually start reading the options
                // of every site stored inside the database
                foreach($folders as $folder)
                {
                    $this->getOptionsFromDatabase($folder, $realConfig[$folder]);
                }
            }

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
        $config['default']['dbname']    = '';
        $config['default']['dbuser']    = '';
        $config['default']['dbpass']    = '';
        $config['default']['dbhost']    = '';
        $config['default']['dbcharset'] = '';
        $config['default']['dbcollate'] = '';
        $config['default']['dbprefix']  = '';
        $config['default']['dbport']    = '';

        // Other
        $config['default']['sitename'] = '';
        $config['default']['cookie_domain'] = '';
        $config['default']['live_site'] = '';

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

        // Sadly Drupal configuration file is a simple PHP file, where people can (and will!) modify it
        // so we can't just include it because we could have "funny" surprise
        // The only option is to parse it
        $contents = file_get_contents($file);

        $tokenizer = new AUtilsPhptokenizer($contents);

        $skip   = 0;
        $error  = false;
        $tokens = array();

        // Database info
        while(!$error)
        {
            try
            {
                // Let's try to extract all the occurrences until we get an error. Since it's just a PHP array,
                // you could write it in a million of different ways
                $info = $tokenizer->searchToken('T_VARIABLE', '$databases', $skip);

                $skip     = $info['endLine'] + 1;
                $tokens[] = $info['data'];
            }
            catch(RuntimeException $e)
            {
                $error = true;
            }
        }

        // Ok, now I got all the fragments I can truly evaluate them
        $databases = $this->extractVariables($tokens, 'databases');

        if(isset($databases['default']) && isset($databases['default']['default']))
        {
            $curSettings = $databases['default']['default'];

            $config['driver']   = $curSettings['driver'];
            $config['dbhost']   = $curSettings['host'];
            $config['dbport']   = isset($curSettings['port']) ? $curSettings['port'] : null;
            $config['dbuser']   = $curSettings['username'];
            $config['dbpass']   = $curSettings['password'];
            $config['dbname']   = $curSettings['database'];
            $config['dbprefix'] = $curSettings['prefix'];
        }

        // Cookie domain
        try
        {
            $fragment = $tokenizer->searchToken('T_VARIABLE', '$cookie_domain');
            $config['cookie_domain'] = $this->extractVariables($fragment['data'], 'cookie_domain');
        }
        catch(RuntimeException $e)
        {
            $config['cookie_domain'] = null;
        }

        // Base url
        try
        {
            $fragment = $tokenizer->searchToken('T_VARIABLE', '$base_url');
            $config['live_site'] = $this->extractVariables($fragment['data'], 'base_url');
        }
        catch(RuntimeException $e)
        {
            $config['live_site'] = null;
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
            $file = APATH_ROOT . '/sites/default/settings.php';
        }

		$out = file_get_contents($file);

        // First of all let's write the database info section
        $tokenizer = new AUtilsPhptokenizer($out);
        $key       = $this->input->getCmd('substep', 'default');

		$dbDriver = $this->get('dbtype', null, $key);
		$dbDriver = strtolower($dbDriver);

		if (in_array($dbDriver, array('mysql', 'mysqli', 'pdomysql')))
		{
			// There's only a "mysql" driver in Drupal
			$dbDriver = 'mysql';
		}

        $replace_db[] = <<<PHP
\$databases['default']['default'] = array(
      'driver' => '$dbDriver',
      'database' => '{$this->get('db', null, $key)}',
      'username' => '{$this->get('user', null, $key)}',
      'password' => '{$this->get('password', null, $key)}',
      'host' => '{$this->get('host', null, $key)}',
      'prefix' => '{$this->get('dbprefix', null, $key)}',
);
PHP;

        $skip  = 0;
        $error = false;

        // In Drupal you can have several database configuration (one master + several slaves). Of course we can't modify
        // those info, too, so we're going to wipe out everything and use only the master configuration
        while(!$error)
        {
            try
            {
                // First time I really want to replace data, in the next loops I simply want to wipe out everything
                if($replace_db)
                {
                    $replace = array_shift($replace_db);
                }
                else
                {
                    $replace = '';
                }

                $out  = $tokenizer->replaceToken('T_VARIABLE', '$databases', $skip, $replace);

                $tokenizer->setCode($out);

                $info = $tokenizer->searchToken('T_VARIABLE', '$databases', $skip);
                $skip = $info['endLine'] + 1;
            }
            catch(RuntimeException $e)
            {
                $error = true;
            }
        }

        // Now let's try to change some other params stored inside the settings file
        $random = new AUtilsRandval();

        // New Salt
        $new_salt = '$drupal_hash_salt = \''.substr(base64_encode($random->generate(43)), 0, 43).'\';';
        $out      = $tokenizer->replaceToken('T_VARIABLE', '$drupal_hash_salt', 0, $new_salt);

        $tokenizer->setCode($out);

        // --- Base Url
        $old_url = $this->get('old_live_site', null, $key);
        $new_url = $this->get('live_site', null, $key);

        // Previously there was a base url and now there isn't
        if($old_url && !$new_url)
        {
            $out = $tokenizer->replaceToken('T_VARIABLE', '$base_url', 0, '# $base_url = \'\';');
        }
        // The url was already there, let's update its value
        elseif($old_url && $new_url && ($old_url != $new_url))
        {
            $out = $tokenizer->replaceToken('T_VARIABLE', '$base_url', 0, '$base_url = \''.$new_url.'\';');
        }
        // The url wasn't set and now it is
        elseif(!$old_url && $new_url)
        {
            $out .= "\n".'$base_url = \''.$new_url.'\';';
        }

        $tokenizer->setCode($out);

        // --- Cookie domain
        $old_domain = $this->get('old_cookie_domain', null, $key);
        $new_domain = $this->get('cookie_domain', null, $key);

        // Previously there was a cookie domain and now there isn't
        if($old_domain && !$new_domain)
        {
            $out = $tokenizer->replaceToken('T_VARIABLE', '$cookie_domain', 0, '# $cookie_domain = \'\';');
        }
        // The domain was already there, let's update its value
        elseif($old_domain && $new_domain && ($old_domain != $new_domain))
        {
            $out = $tokenizer->replaceToken('T_VARIABLE', '$cookie_domain', 0, '$cookie_domain = \''.$new_domain.'\';');
        }
        // The domain wasn't set and now it is
        elseif(!$old_domain && $new_domain)
        {
            $out .= "\n".'$cookie_domain = \''.$new_domain.'\';';
        }

		return $out;
	}

    /**
     * Writes the new config params inside the wp-config file and the database.
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
        $key = $this->input->getCmd('substep', 'default');

        $connectionVars = $this->getDatabase($key);

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

        // Site name
        $query = $db->getQuery(true)
                    ->update($db->qn('#__variable'))
                    ->set($db->qn('value') . ' = ' . $db->q(serialize($this->get('sitename', null, $key))))
                    ->where($db->qn('name') . ' = ' . $db->q('site_name'));
        $db->setQuery($query)->execute();

        // User should always provide that, if they blindly leave it blank I can only use the PHP one and hope for the best
        $tmpPath = $this->get('tmp_path', sys_get_temp_dir(), $key);

        $query = $db->getQuery(true)
                    ->update($db->qn('#__variable'))
                    ->set($db->qn('value') . ' = ' . $db->q(serialize($tmpPath)))
                    ->where($db->qn('name') . ' = ' . $db->q('file_temporary_path'));
        $db->setQuery($query)->execute();

        // Update CRON key
        $query = $db->getQuery(true)
                    ->update($db->qn('#__variable'))
                    ->set($db->qn('value') . ' = ' . $db->q(serialize($this->get('cron_key', null, $key))))
                    ->where($db->qn('name') . ' = ' . $db->q('cron_key'));
        $db->setQuery($query)->execute();

        // Update private key
        $query = $db->getQuery(true)
            ->update($db->qn('#__variable'))
            ->set($db->qn('value') . ' = ' . $db->q(serialize($this->get('drupal_private_key', null, $key))))
            ->where($db->qn('name') . ' = ' . $db->q('drupal_private_key'));
        $db->setQuery($query)->execute();

        // Update site email
        $query = $db->getQuery(true)
                    ->update($db->qn('#__variable'))
                    ->set($db->qn('value') . ' = ' . $db->q(serialize($this->get('site_mail', null, $key))))
                    ->where($db->qn('name') . ' = ' . $db->q('site_mail'));
        $db->setQuery($query)->execute();

        $new_config = $this->getFileContents($file);

        if ( !file_put_contents($file, $new_config))
        {
            return false;
        }

        return true;
    }

	/**
	 * Gets a configuration value. We have to override the parent since Drupal supports multi site installations
	 *
	 * @param   string  $key        The key (variable name)
	 * @param   mixed   $default    The default value to return if the key doesn't exist
     * @param   string  $namespace  Namespace containing the config value. Namely, the folder holding the settings.php file
	 *
	 * @return  mixed  The variable's value
	 */
	public function get($key, $default = null, $namespace = 'default')
	{
		if (array_key_exists($key, $this->configvars[$namespace]))
		{
			return $this->configvars[$namespace][$key];
		}
		else
		{
			// The key was not found. Set it with the default value, store and
			// return the default value
			$this->configvars[$namespace][$key] = $default;
			$this->saveToSession();

			return $default;
		}
	}

	/**
	 * Sets a variable's value and stores the configuration array in the global
	 * Storage. We have to override the parent since Drupal supports multi site installations
	 *
	 * @param   string $key   The variable name
	 * @param   mixed  $value The value to set it to
     * @param   string  $namespace  Namespace containing the config value. Namely, the folder holding the settings.php file
	 */
	public function set($key, $value, $namespace = 'default')
	{
		$this->configvars[$namespace][$key] = $value;
		$this->saveToSession();
	}

    /**
     * Merges configuration options read from the settings.php file with the options stored inside the database
     *
     * @param   string  $key        Site key we are currently working on
     * @param   array   $config     Configuration loaded from the settings.php file
     */
    protected function getOptionsFromDatabase($key, &$config)
    {
        $connectionVars = $this->getDatabase($key);

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
                $db->q('file_temporary_path'),
                $db->q('site_mail'),
                $db->q('site_name')
            );

            $query = $db->getQuery(true)
                        ->select(array($db->qn('name'), $db->qn('value')))
                        ->from('#__variable')
                        ->where($db->qn('name') . ' IN (' . implode(',', $searchFor) . ')');
            $options = $db->setQuery($query)->loadObjectList();

            foreach ($options as $option)
            {
                $key = $option->name;

                // Let's normalize the name of the config vars
                if($option->name == 'file_temporary_path')
                {
                    $key = 'tmp_path';
                }
                elseif($option->name == 'site_name')
                {
                    $key = 'sitename';
                }

                // Strings are stored as serialized objects...
                $config[$key] = unserialize($option->value);
            }
        }
        catch (Exception $exc)
        {
            // Well, what the hell...
        }
    }

    /**
     * Extracts a variable from a piece of PHP code. We use a dedicated function to minimize conflicts
     *
     * @param   array   $fragments      On or more PHP fragments
     * @param   string  $variableName   Name of the variable that should be extracted
     *
     * @return  mixed
     */
    public function extractVariables($fragments, $variableName)
    {
        $fragments = (array) $fragments;

        // Let's evaluate the code of every fragment. Since eval() could be disabled, let's do the write + include trick
        foreach($fragments as $fragment)
        {
			// Sanity check for open/close comments
			$lines = explode("\n", $fragment);
			$clean = array();

			foreach ($lines as $line)
			{
				$line = trim($line);

				// This should take care of closing tag comment (*/) and running comment (*)
				if (strpos($line, '*') === 0)
				{
					continue;
				}

				if (strpos($line, '/*') === 0)
				{
					continue;
				}

				$clean[] = $line;
			}

			$fragment = implode("\n", $clean);
			$file 	  = tempnam(APATH_TEMPINSTALL, 'angie');

            file_put_contents($file, "<?php \n".$fragment);

            @include $file;

            @unlink($file);
        }

        if(isset($$variableName))
        {
            return $$variableName;
        }

        return null;
    }

    public function getDatabase($key = null)
    {
        /** @var AngieModelDatabase $model */
        $model           = AModel::getAnInstance('Database', 'AngieModel', array(), $this->container);
        $keys            = $model->getDatabaseNames();

        // Do I have a multidb environment or everything is stored inside the same db with a different prefix
        $multidb = $this->configvars['multidb'];
        // Do I have a host mapping (domain name changed)?
        $hostMapping = $this->get('hostMapping', array(), 'default');

        // Separated databases, this is the easiest scenario
        if($multidb)
        {
            if(!$key || $key == 'default')
            {
                $key = array_shift($keys);
            }
            else
            {
                // Do I have a mapping for this key? If so let's use that
                if(isset($hostMapping[$key]))
                {
                    $key = $hostMapping[$key];
                }
                
                // Let's perform a partial search
                foreach($keys as $storedKey)
                {
                    if(strpos($storedKey, $key) !== false)
                    {
                        $key = $storedKey;
                        break;
                    }
                }
            }

            return $model->getDatabaseInfo($key);
        }
        else
        {
            // I want the main database, that's easy, it's the first key
            if ($key == 'default' || !$key)
            {
                return $model->getDatabaseInfo(array_shift($keys));
            }

            // I want a subsite one, but we're in a monodb environment
            // So lets get the main one
            $subsite = $model->getDatabaseInfo(array_shift($keys));

            // And change the prefix with the subsite one
            $subsite->prefix = $this->get('dbprefix', '', $key);

            return $subsite;
        }
    }

    /**
     * Searches inside the site installation if there are additional settings.php files (ie we are in a multisite environment)
     *
     * @return array
     */
    public function getSettingsFolders()
    {
        // Do I have a multi-site environment? If so I have to display the setup page several times
        $iterator     = new DirectoryIterator(APATH_ROOT.'/sites');
        $directories  = array();
        $extraFolders = array('default');

        // First of all let's get all the directories. I have to exclude the "all" one since there could be a massive
        // amount of files/directories
        foreach($iterator as $file)
        {
            if($file->isDot() || !$file->isDir())
            {
                continue;
            }

            // Let's skip the "all" and "default" one, additional sites can't be there
            if($file->getFilename() == 'all' || $file->getFilename() == 'default')
            {
                continue;
            }

            $directories[] = $file->getPathname();
        }

        foreach($directories as $directory)
        {
            if(file_exists($directory.'/settings.php'))
            {
                $extraFolders[] = basename($directory);
            }
        }

        return $extraFolders;
    }
}
