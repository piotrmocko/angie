<?php
/**
 * @package angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieModelMagentoConfiguration extends AngieModelBaseConfiguration
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
			$realConfig = $this->loadFromFile(APATH_CONFIGURATION . '/app/etc/local.xml');

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
     * @param   string $file The full path to the file
     *
     * @return array
     */
    public function loadFromFile($file)
    {
        $config          = array();

        $xml       = new SimpleXMLElement($file, 0 , true);
        $resources = $xml->global->resources;

        $config['dbhost']   = (string) $resources->default_setup->connection->host;
        $config['dbuser']   = (string) $resources->default_setup->connection->username;
        $config['dbpass']   = (string) $resources->default_setup->connection->password;
        $config['dbname']   = (string) $resources->default_setup->connection->dbname;
        $config['dbprefix'] = (string) $resources->db->table_prefix;

        $config['adminurl'] = (string) $xml->admin->routers->adminhtml->args->frontName;

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
            $file = APATH_ROOT.'/app/etc/local.xml';
        }

        $xml       = new SimpleXMLElement($file, 0 , true);
        $resources = $xml->global->resources;

        $config['dbhost']   = (string) $resources->default_setup->connection->host = $this->get('dbhost');
        $config['dbuser']   = (string) $resources->default_setup->connection->username = $this->get('dbuser');
        $config['dbpass']   = (string) $resources->default_setup->connection->password = $this->get('dbpass');
        $config['dbname']   = (string) $resources->default_setup->connection->dbname = $this->get('dbname');
        $config['dbprefix'] = (string) $resources->db->table_prefix = $this->get('dbprefix');

        $config['adminurl'] = (string) $xml->admin->routers->adminhtml->args->frontName = $this->get('adminurl');

        $new_config = $xml->asXML();

        return $new_config;
    }

    /**
     * Writes the new config params inside the wp-config file and the database.
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

        // If supplied in the request (ie restoring using UNiTE) let's use that URL
        $livesite = $this->get('livesite', '');

        if(!$livesite)
        {
            $livesite = AUri::root();
        }

        $url = str_replace('/installation', '', $livesite);

        // The URL must end with a slash
        $url = rtrim($url, '/').'/';

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

        $new_config = $this->getFileContents($file);

        if(!file_put_contents($file, $new_config))
        {
            return false;
        }

        return true;
    }
}
