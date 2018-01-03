<?php
/**
 * @package angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieModelMagento2Configuration extends AngieModelBaseConfiguration
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
			$realConfig = $this->loadFromFile();

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
        $config['dbname']       = '';
        $config['dbuser']       = '';
        $config['dbpass']       = '';
        $config['dbhost']       = '';
        $config['dbprefix']     = '';

        // Other
        $config['adminurl'] = '';

        return $config;
    }

    /**
     * Loads the configuration information from a PHP file
     *
     * @return array
     */
    public function loadFromFile()
    {
        $orig_config = include APATH_ROOT . '/app/etc/env.php';

        $config['dbhost']   = $orig_config['db']['connection']['default']['host'];
        $config['dbuser']   = $orig_config['db']['connection']['default']['username'];
        $config['dbpass']   = $orig_config['db']['connection']['default']['password'];
        $config['dbname']   = $orig_config['db']['connection']['default']['dbname'];

        $config['dbprefix'] = $orig_config['db']['table_prefix'];
        $config['adminurl'] = $orig_config['backend']['frontName'];

		return $config;
    }

    /**
     * Creates the string that will be put inside the new configuration file.
     * This is a separate function so we can show the content if we're unable to write to the filesystem
     * and ask the user to manually do that.
     */
    public function getFileContents()
    {
        $orig_config = include APATH_ROOT . '/app/etc/env.php';

        $orig_config['db']['connection']['default']['host']     = $this->get('dbhost');
        $orig_config['db']['connection']['default']['username'] = $this->get('dbuser');
        $orig_config['db']['connection']['default']['password'] = $this->get('dbpass');
        $orig_config['db']['connection']['default']['dbname']   = $this->get('dbname');

        $orig_config['db']['table_prefix']   = $this->get('dbprefix');
        $orig_config['backend']['frontName'] = $this->get('adminurl');

        // We are going to write inside a PHP file some parsable content
        return "<?php \nreturn ".var_export($orig_config, true).';';
    }

    /**
     * Writes the new config params inside the config file and the database.
     *
     * @return bool
     */
    public function writeConfig()
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

        // If supplied in the request (ie restoring using UNiTE) let's use that URL
        $livesite = $this->get('livesite', '');

        if(!$livesite)
        {
            $livesite = AUri::root();
        }

        $url = str_replace('/installation', '', $livesite);

        $query = $db->getQuery(true)
                    ->update($db->qn('#__core_config_data'))
                    ->set($db->qn('value').' = '.$db->q($url))
                    ->where($db->qn('path').' = '.$db->q('web/unsecure/base_url'));
        $db->setQuery($query)->execute();

        $query = $db->getQuery(true)
                    ->update($db->qn('#__core_config_data'))
                    ->set($db->qn('value').' = '.$db->q($url))
                    ->where($db->qn('path').' = '.$db->q('web/secure/base_url'));
        $db->setQuery($query)->execute();

        $new_config = $this->getFileContents();

        if(!file_put_contents(APATH_ROOT . '/app/etc/env.php', $new_config))
        {
            return false;
        }

        return true;
    }
}
