<?php
/**
 * @package angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieModelOctobercmsConfiguration extends AngieModelBaseConfiguration
{
	public function __construct($config = array(), AContainer $container = null)
	{
		// Call the parent constructor
		parent::__construct($config, $container);

		// Load the configuration variables from the session or the default configuration shipped with ANGIE
		$this->configvars = $this->container->session->get('configuration.variables');

		if (empty($this->configvars))
		{
			$this->configvars = array_merge($this->getDefaultConfig(), $this->loadFromFile());

			$this->saveToSession();
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
	    $config['backendUri']   = '';

        return $config;
    }

    /**
     * Loads the configuration information from a PHP file
     *
     * @return array
     */
    public function loadFromFile()
    {
        $config = array();

        $db_file = APATH_ROOT.'/config/database.php';

        // TODO Do I really need to get these info?
	    if (file_exists($db_file))
	    {
		    $db_config = include $db_file;

		    $dbType = $db_config['default'];

		    $connection = $db_config['connections'][$dbType];

		    $config['driver']   = $connection['driver'];
		    $config['dbprefix'] = $connection['prefix'];

		    // We have such info only if we're using MySQL
		    if($config['driver'] != 'sqlite')
		    {
			    $config['dbhost']   = $connection['host'];
			    $config['dbuser']   = $connection['username'];
			    $config['dbpass']   = $connection['password'];
			    $config['dbname']   = $connection['database'];
		    }
	    }

	    $cms_file = APATH_ROOT.'/config/cms.php';

	    if (file_exists($cms_file))
	    {
		    $cms_config = include $cms_file;

		    $config['backendUri'] = $cms_config['backendUri'];
	    }

		return $config;
    }

    /**
     * Writes the new config params inside the config file and the database.
     *
     * @return bool
     */
    public function writeConfig()
    {
    	// CMS configuration details are spread across multiple files, so I have to do everything in one shot
    	$cms_file = APATH_ROOT.'/config/cms.php';
	    $cms_config = include $cms_file;

	    $cms_config['backendUri'] = $this->get('backendUri');

	    $new_config = "<?php return ".var_export($cms_config, true).';';

	    if(!file_put_contents($cms_file, $new_config))
	    {
		    return false;
	    }

	    $app_file = APATH_ROOT.'/config/app.php';
	    $contents = file_get_contents($app_file);

	    $new_config = preg_replace('#["-\']url["-\']\s?=>\s?(.*?)\s?,#', "'url' => '".$this->get('appurl')."',", $contents);

	    if(!file_put_contents($app_file, $new_config))
	    {
		    return false;
	    }

	    $db_file = APATH_ROOT.'/config/database.php';

	    $db_config = include $db_file;
	    $dbType = $db_config['default'];

	    $db_config['connections'][$dbType]['prefix'] = $this->get('dbprefix');

	    if ($dbType != 'sqlite')
	    {
		    $db_config['connections'][$dbType]['host']     = $this->get('dbhost');
		    $db_config['connections'][$dbType]['username'] = $this->get('dbuser');
		    $db_config['connections'][$dbType]['password'] = $this->get('dbpass');
		    $db_config['connections'][$dbType]['database'] = $this->get('dbname');
	    }

	    $new_config = "<?php return ".var_export($db_config, true).';';

	    if(!file_put_contents($db_file, $new_config))
	    {
		    return false;
	    }

        return true;
    }
}
