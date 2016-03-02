<?php
/**
 * @package   angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author    Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieModelMauticConfiguration extends AngieModelBaseConfiguration
{
	public function __construct($config = array(), AContainer $container = null)
	{
		// Call the parent constructor
		parent::__construct($config, $container);

		// Load the configuration variables from the session or the default configuration shipped with ANGIE
		$this->configvars = $this->container->session->get('configuration.variables');

		if (empty($this->configvars))
		{
			$this->configvars = $this->loadFromFile(APATH_ROOT . '/app/config/local.php');

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
        $config['dbname']    = '';
        $config['dbuser']    = '';
        $config['dbpass']    = '';
        $config['dbhost']    = '';
        $config['dbprefix']  = '';
        $config['dbport']    = '';

        // Other
        $config['live_site'] = '';

        return $config;
    }

    /**
     * Loads the configuration information from a PHP file
     *
     * @param   string  $file   The full path to the file
     *
     * @return array
     */
	public function loadFromFile($file)
	{
		$ret = array();

        if (file_exists($file))
        {
            include $file;

            /** @var array $parameters */
            $ret['dbname']    = $parameters['db_name'];
            $ret['dbuser']    = $parameters['db_user'];
            $ret['dbpass']    = $parameters['db_password'];
            $ret['dbhost']    = $parameters['db_host'];
            $ret['dbprefix']  = $parameters['db_table_prefix'];
            $ret['dbport']    = $parameters['db_port'];

            // I have to save the old site url, since I'll have to replace it inside the database
            $ret['old_live_site'] = $parameters['site_url'];
            $ret['live_site'] = $parameters['site_url'];
            $ret['log_path']  = $parameters['log_path'];
            $ret['mailfrom']  = $parameters['mailer_from_email'];
            $ret['fromname']  = $parameters['mailer_from_name'];
        }

		return $ret;
	}

	/**
	 * Get the contents of the app/config/local.php file
	 *
	 * @return  string  The contents of the app/config/local.php file
	 */
	public function getFileContents()
	{
		// First of all let's include the settings file
        if(!file_exists(APATH_SITE . '/app/config/local.php'))
        {
            return '';
        }

        /** @var array $parameters */
        include APATH_SITE . '/app/config/local.php';

        // Then let's update the variable with the new values
        $parameters['mailer_from_name']     = $this->get('fromname');
        $parameters['mailer_from_email']    = $this->get('mailfrom');
        $parameters['log_path']             = $this->get('log_path');
        $parameters['site_url']             = $this->get('live_site');
        $parameters['db_name']              = $this->get('db');
        $parameters['db_user']              = $this->get('user');
        $parameters['db_password']          = $this->get('password');
        $parameters['db_host']              = $this->get('host');
        $parameters['db_table_prefix']      = $this->get('dbprefix');
        $parameters['db_port']              = $this->get('dbport');
        $parameters['secret_key']           = $this->get('secret');

        // Finally let's compose the string
        $out  = "<?php\n";
        $out .= '$parameters = array('."\n";

        foreach($parameters as $key => $value)
        {
            if(is_null($value))
            {
                $value = 'null';
            }
            else
            {
                $value = "'".$value."'";
            }

            $out .= "    '".$key."' => ".$value.",\n";
        }

        $out .= ");\n";

		return $out;
	}
}