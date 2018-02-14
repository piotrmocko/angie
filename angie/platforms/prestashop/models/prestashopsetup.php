<?php

/**
 * @package angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */
defined('_AKEEBA') or die();

class AngieModelPrestashopSetup extends AngieModelBaseSetup
{
	/**
	 * Cached copy of the configuration model
	 *
	 * @var  AngieModelPrestashopConfiguration
	 */
	protected $configModel = null;

	/**
	 * Gets the basic site parameters
	 *
	 * @return  array
	 */
	protected function getSiteParamsVars()
	{
        $siteurl = str_replace('/installation/', '', AUri::root());

		$ret = array(
			'sitename'		 => $this->getState('sitename', $this->configModel->get('sitename', 'Restored website')),
			'siteurl'		 => $this->getState('siteurl' , $siteurl)
		);

        require_once APATH_INSTALLATION.'/angie/helpers/setup.php';

        $ret['siteurl'] = AngieHelperSetup::cleanLiveSite($ret['siteurl']);

        $this->configModel->set('siteurl', $ret['siteurl']);

		return $ret;
	}

	protected function getSuperUsersVars()
	{
		$ret = array();

		try
		{
            // Connect to the database
			$db = $this->getDatabase();
		}
		catch (Exception $exc)
		{
			return $ret;
		}

		try
		{
            $query = $db->getQuery(true)
                        ->select(array(
                            $db->qn('id_employee').' AS '.$db->qn('id'),
                            "CONCAT(".$db->qn('firstname').", ' ',".$db->qn('lastname')." ) AS ".$db->qn('username'),
                            $db->qn('email')
                        ))
                        ->from($db->qn('#__employee'))
                        ->where($db->qn('id_profile').' = '.$db->q(1));

            $ret['superusers'] = $db->setQuery($query)->loadObjectList(0);
		}
		catch (Exception $exc)
		{
			return $ret;
		}

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
		$this->configModel->set('siteurl', $stateVars->siteurl);

		// -- Database settings
		$connectionVars = $this->getDbConnectionVars();
		$this->configModel->set('dbtype'  , $connectionVars->dbtype);
		$this->configModel->set('dbhost'  , $connectionVars->dbhost);
		$this->configModel->set('dbuser'  , $connectionVars->dbuser);
		$this->configModel->set('dbpass'  , $connectionVars->dbpass);
		$this->configModel->set('dbname'  , $connectionVars->dbname);
		$this->configModel->set('dbprefix', $connectionVars->prefix);

        // WARNING! DO NOT TOUCH THE COOKIE VALUES!
        // Passwords are salted with that value, so if you touch them ALL passwords become invalid!!!

		$this->configModel->saveToSession();

		// Sanity check
		if(!$stateVars->siteurl)
		{
			throw new Exception(AText::_('SETUP_SITEURL_REQUIRED'));
		}

		// Apply the Super Administrator changes
		$this->applySuperAdminChanges();

		// Get the configuration file and try to save it
		if (!$this->configModel->writeConfig())
		{
			return false;
		}

		return true;
	}

	private function applySuperAdminChanges()
	{
		// Get the Super User ID. If it's empty, skip.
		$id = $this->getState('superuserid', 0);
		if (!$id)
		{
			return false;
		}

		// Get the Super User email and password
		$email     = $this->getState('superuseremail', '');
		$password1 = $this->getState('superuserpassword', '');
		$password2 = $this->getState('superuserpasswordrepeat', '');

		// If the email is empty but the passwords are not, fail
		if (empty($email))
		{
			if(empty($password1) && empty($password2))
			{
				return false;
			}
			else
			{
				throw new Exception(AText::_('SETUP_ERR_EMAILEMPTY'));
			}
		}

		// If the passwords are empty, skip
		if (empty($password1) && empty($password2))
		{
			return false;
		}

		// Make sure the passwords match
		if ($password1 != $password2)
		{
			throw new Exception(AText::_('SETUP_ERR_PASSWORDSDONTMATCH'));
		}

		// Connect to the database
        $db	= $this->getDatabase();

		// Create a new encrypted password. We will use the cookie key as salt
		$crypt = md5($this->configModel->get('cookiekey').$password1);

		// Update the database record
		$query = $db->getQuery(true)
                    ->update($db->qn('#__employee'))
                    ->set($db->qn('passwd') . ' = ' . $db->q($crypt))
                    ->set($db->qn('email') . ' = ' . $db->q($email))
                    ->where($db->qn('id_employee') . ' = ' . $db->q($id));
		$db->setQuery($query)->execute();

		return true;
	}
}
