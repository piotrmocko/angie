<?php

/**
 * @package   angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author    Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */
defined('_AKEEBA') or die();

class AngieModelOctobercmsSetup extends AngieModelBaseSetup
{
	/**
	 * Gets the basic site parameters
	 *
	 * @return  array
	 */
	protected function getSiteParamsVars()
	{
		$wwwroot = $this->input->get('livesite', AUri::root());
		$wwwroot = str_replace('/installation/', '', $wwwroot);

		$ret = array(
			'appurl'    => $this->getState('appurl', $this->configModel->get('appurl', $wwwroot)),
			'backendUri' => $this->getState('backendUri', $this->configModel->get('backendUri', '/backend')),
		);

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
					$db->qn('id'),
					$db->qn('login', 'username'),
					$db->qn('email')
				))
				->from($db->qn('#__backend_users'))
				->where($db->qn('is_superuser').' = '.$db->q(1));

			$ret['superusers'] = $db->setQuery($query)->loadObjectList();
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
		$this->configModel->set('appurl', $stateVars->appurl);
		$this->configModel->set('backendUri', $stateVars->backendUri);

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

		// Let's load the password compatibility file
		require_once APATH_ROOT.'/installation/framework/utils/password.php';

		// Create a new bCrypt-bashed password. At the time of this writing (December 2016) October CMS is using a cost of 10
		$cryptpass = password_hash($password1, PASSWORD_BCRYPT, array('cost' => 10));

		// Update the database record
		$db = $this->getDatabase();

		$query = $db->getQuery(true)
			->update($db->qn('#__backend_users'))
			->set($db->qn('password') . ' = ' . $db->q($cryptpass))
			->set($db->qn('email') . ' = ' . $db->q($email))
			->where($db->qn('id') . ' = ' . $db->q($id));
		$db->setQuery($query)->execute();

		return true;
	}
}
