<?php

/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */
defined('_AKEEBA') or die();

class AngieModelPhpbbSetup extends AngieModelBaseSetup
{
	/**
	 * Gets the basic site parameters
	 *
	 * @return  array
	 */
	protected function getSiteParamsVars()
	{
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
                    ->select($db->qn('config_value'))
                    ->from($db->qn('#__config'))
                    ->where($db->qn('config_name').' = '.$db->q('sitename'));
        try
        {
            $sitename = $db->setQuery($query)->loadResult();
        }
        catch (Exception $e)
        {
            $sitename  = 'Restored website';
        }

        $query = $db->getQuery(true)
            ->select($db->qn('config_value'))
            ->from($db->qn('#__config'))
            ->where($db->qn('config_name').' = '.$db->q('site_desc'));
        try
        {
            $sitedescr = $db->setQuery($query)->loadResult();
        }
        catch (Exception $e)
        {
            $sitedescr = 'Restored website description';
        }


        $siteurl = str_replace('/installation/', '', AUri::root());

		$ret = array(
			'sitename'		 => $this->getState('sitename' , $sitename),
			'sitedescr'		 => $this->getState('sitedescr', $sitedescr),
			'siteurl'		 => $this->getState('siteurl'  , $siteurl)
		);

        require_once APATH_INSTALLATION.'/angie/helpers/setup.php';

        $ret['siteurl'] = AngieHelperSetup::cleanLiveSite($ret['siteurl']);

		return $ret;
	}

	protected function getSuperUsersVars()
	{
		$ret = array();

        // Getting user privileges under phpBB is a royal PITA, since they are stored as encoded
        // binary strings. So let's avoid this step

		return $ret;
	}

	/**
	 * Apply the settings to the configuration file and the database
	 */
	public function applySettings()
	{
		// Get the state variables and update the global configuration
		$stateVars = $this->getStateVariables();

		// -- General settings
		$this->configModel->set('sitename', $stateVars->sitename);
		$this->configModel->set('sitedescr', $stateVars->sitedescr);
		$this->configModel->set('siteurl', $stateVars->siteurl);

		// -- Database settings
		$connectionVars = $this->getDbConnectionVars();
		$this->configModel->set('dbtype'  , $connectionVars->dbtype);
		$this->configModel->set('dbhost'  , $connectionVars->dbhost);
		$this->configModel->set('dbuser'  , $connectionVars->dbuser);
		$this->configModel->set('dbpass'  , $connectionVars->dbpass);
		$this->configModel->set('dbname'  , $connectionVars->dbname);
		$this->configModel->set('dbprefix', $connectionVars->prefix);

		$this->configModel->saveToSession();

		// Sanity check
		if(!$stateVars->siteurl)
		{
			throw new Exception(AText::_('SETUP_SITEURL_REQUIRED'));
		}

		// Apply the Super Administrator changes
		$this->applySuperAdminChanges();

		// Get the wp-config.php file and try to save it
		if (!$this->configModel->writeConfig(APATH_ROOT.'/config.php'))
		{
			return false;
		}

		return true;
	}

	private function applySuperAdminChanges()
	{
        // Getting user privileges under phpBB is a royal PITA, since they are stored as encoded
        // binary strings. So let's avoid this step

		return true;
	}
}