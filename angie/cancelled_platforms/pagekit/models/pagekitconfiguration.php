<?php
/**
 * @package angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieModelPagekitConfiguration extends AngieModelBaseConfiguration
{
	public function __construct($config = array(), AContainer $container = null)
	{
		// Call the parent constructor
		parent::__construct($config, $container);

		// Load the configuration variables from the session or the default configuration shipped with ANGIE
		$this->configvars = $this->container->session->get('configuration.variables');

		if (empty($this->configvars) || empty($this->configvars['sitename']))
		{
			$this->configvars = $this->getDefaultConfig();
			$realConfig       = array();

			if (empty($this->configvars['sitename']))
			{
				$realConfig = $this->loadFromFile(APATH_CONFIGURATION . '/config.php');
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
        $config['dbname']       = '';
        $config['dbuser']       = '';
        $config['dbpass']       = '';
        $config['dbhost']       = '';
        $config['dbprefix']     = '';

        // Other
        $config['sitename'] = '';

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

	    if (file_exists($file))
	    {
		    $pageKitConfig = include $file;

		    $config['driver'] = $pageKitConfig['database']['default'];

		    $connection = $pageKitConfig['database']['connections'][$config['driver']];

		    $config['dbprefix'] = $connection['prefix'];

		    // We have such info only if we're using MySQL
		    if($config['driver'] != 'sqlite')
		    {
			    $config['dbhost']   = $connection['host'];
			    $config['dbuser']   = $connection['user'];
			    $config['dbpass']   = $connection['password'];
			    $config['dbname']   = $connection['dbname'];
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
	    // Ok, first of all let's include the current file
	    $config = include $file;

	    // Then let's change the connection params
	    $driver = $this->get('dbtype');
	    $config['database']['default'] = $driver;

	    // Let's unset the whole "connections" index, so I can rewrite it again
	    unset($config['database']['connections']);

	    $config['database']['connections'][$driver]['prefix'] = $this->get('dbprefix');

	    if ($driver != 'sqlite')
	    {
		    $config['database']['connections'][$driver]['host']     = $this->get('dbhost');
		    $config['database']['connections'][$driver]['user']     = $this->get('dbuser');
		    $config['database']['connections'][$driver]['password'] = $this->get('dbpass');
		    $config['database']['connections'][$driver]['dbname']   = $this->get('dbname');
	    }

	    $new_config = "<?php return ".var_export($config, true).';';

        return $new_config;
    }

    /**
     * Writes the new config params inside the config file and the database.
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

	    $name = $this->get('dbtype');
	    $options = array(
		    'database'	 => $this->get('dbname'),
		    'select'	 => 1,
		    'host'		 => $this->get('dbhost'),
		    'user'		 => $this->get('dbuser'),
		    'password'	 => $this->get('dbpass'),
		    'prefix'	 => $this->get('dbprefix')
	    );

	    $db = ADatabaseFactory::getInstance()->getDriver($name, $options);

	    // Update the site name
	    $query = $db->getQuery(true)
				    ->select($db->qn('value'))
				    ->from('#__system_config')
				    ->where($db->qn('name') . ' = ' . $db->q('system/site'));
	    $pk_options = $db->setQuery($query)->loadResult();

	    $pk_options = json_decode($pk_options, true);
	    $pk_options['title'] = $this->get('sitename', '');

	    $pk_options = json_encode($pk_options);

	    $query = $db->getQuery(true)
		            ->update($db->qn('#__system_config'))
		            ->set($db->qn('value') . ' = ' . $db->q($pk_options))
		            ->where($db->qn('name') . ' = ' . $db->q('system/site'));
	    $db->setQuery($query)->execute();

	    $new_config = $this->getFileContents($file);

	    if(!file_put_contents($file, $new_config))
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
		// PageKit has some options set inside the db, too
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

			$query = $db->getQuery(true)
						->select($db->qn('value'))
						->from('#__system_config')
						->where($db->qn('name') . ' = ' . $db->q('system/site'));
			$pk_options = $db->setQuery($query)->loadResult();

			$pk_options = json_decode($pk_options, true);

			if ($pk_options && isset($pk_options['title']))
			{
				$config['sitename'] = $pk_options['title'];
			}
		}
		catch (Exception $exc)
		{
		}
	}
}
