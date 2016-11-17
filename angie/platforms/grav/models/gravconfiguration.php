<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieModelGravConfiguration extends AngieModelBaseConfiguration
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
				$realConfig = $this->loadFromFile(APATH_CONFIGURATION . '/user/config/site.yaml');
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

	    if (!file_exists($file))
	    {
		    return $config;
	    }

	    $yaml = \Symfony\Component\Yaml\Yaml::parse($file);

	    $config['sitename'] = $yaml['title'];

		return $config;
    }

    /**
     * Creates the string that will be put inside the new configuration file.
     * This is a separate function so we can show the content if we're unable to write to the filesystem
     * and ask the user to manually do that.
     */
    public function getFileContents($file = null)
    {
	    $yaml = \Symfony\Component\Yaml\Yaml::parse($file);

	    // TODO Update configuration values

	    $new_config = \Symfony\Component\Yaml\Yaml::dump($yaml);

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
	    $new_config = $this->getFileContents($file);

	    if(!file_put_contents($file, $new_config))
	    {
		    return false;
	    }

        return true;
    }
}