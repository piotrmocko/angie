<?php

/**
 * @package   angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author    Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */
defined('_AKEEBA') or die();

class AngieModelMagento2Setup extends AngieModelBaseSetup
{
	/**
	 * Gets the basic site parameters
	 *
	 * @return  array
	 */
	protected function getSiteParamsVars()
	{
		$ret = array(
			'adminurl' => $this->getState('adminurl', $this->configModel->get('adminurl', 'Restored website')),
			// We need a the URL of the shop, usually ANGIE can detect it, but if we're using UNiTE to restore the
			// backup it can't detect it, so we have to externally supply it
			'livesite' => $this->getState('livesite', $this->configModel->get('livesite', ''))
		);

		// Double check we have a valid livesite URL
		if($ret['livesite'])
		{
			require_once APATH_INSTALLATION.'/angie/helpers/setup.php';

			$ret['livesite'] = AngieHelperSetup::cleanLiveSite($ret['livesite']);
		}

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

		// Get the user information for the Super Administrator users
		try
		{
			$query = $db->getQuery(true)
				->select(array(
					$db->qn('user_id') . ' AS ' . $db->qn('id'),
					$db->qn('username'),
					$db->qn('email')
				))
				->from($db->qn('#__admin_user'));
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
		$this->configModel->set('adminurl', $stateVars->adminurl);
		$this->configModel->set('livesite', $stateVars->livesite);

		// -- Database settings
		$connectionVars = $this->getDbConnectionVars();
		$this->configModel->set('dbtype', $connectionVars->dbtype);
		$this->configModel->set('dbhost', $connectionVars->dbhost);
		$this->configModel->set('dbuser', $connectionVars->dbuser);
		$this->configModel->set('dbpass', $connectionVars->dbpass);
		$this->configModel->set('dbname', $connectionVars->dbname);
		$this->configModel->set('dbprefix', $connectionVars->prefix);

		$this->configModel->saveToSession();

		// Apply the Super Administrator changes
		$this->applySuperAdminChanges();

		// Get the wp-config.php file and try to save it
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
			if (empty($password1) && empty($password2))
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

		$base = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

		for ($i = 0, $salt = '', $lc = strlen($base)-1; $i < 32; $i++)
		{
			$salt .= $base[mt_rand(0, $lc)];
		}

        $hash = hash('sha256', $salt.$password1);

        // We are using sha256, which is mapped as 1 inside Magento 2
		$crypt = $hash.':'.$salt.':1';

        // Update the database record
        $db = $this->getDatabase();

		$query = $db->getQuery(true)
			->update($db->qn('#__admin_user'))
			->set($db->qn('password') . ' = ' . $db->q($crypt))
			->set($db->qn('email') . ' = ' . $db->q($email))
			->where($db->qn('user_id') . ' = ' . $db->q($id));
		$db->setQuery($query)->execute();

		return true;
	}
}