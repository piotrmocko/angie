<?php

/**
 * @package angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */
defined('_AKEEBA') or die();

class AngieModelMoodleSetup extends AngieModelBaseSetup
{
	/**
	 * Gets the basic site parameters
	 *
	 * @return  array
	 */
	protected function getSiteParamsVars()
	{
        // Do I have a www_root coming from UNiTE? If so, use it
        $wwwroot = $this->input->get('livesite', AUri::root());
		$wwwroot = str_replace('/installation/', '', $wwwroot);

		// Create the host from the wwwroot - This regex is not optimal, but let's use the same used by Moodle
		preg_match('|^[a-z]+://([a-zA-Z0-9-.]+)|i', $wwwroot, $matches);
		$wwwhost = $matches[1];

		$ret = array(
			'fullname'  => $this->getState('fullname', $this->configModel->get('fullname', 'Restored Moodle')),
			'shortname' => $this->getState('shortname', $this->configModel->get('shortname', 'Moodle')),
			'wwwroot'   => $this->getState('wwwroot', $wwwroot),
			'dataroot'  => $this->getState('dataroot', $this->configModel->get('dataroot', '')),
			'chat_host' => $this->getState('chat_host', $this->configModel->get('chat_host', $wwwhost)),
			'chat_ip'   => $this->getState('chat_ip', $this->configModel->get('chat_ip', isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : ''))
		);

        require_once APATH_INSTALLATION.'/angie/helpers/setup.php';

        $ret['wwwroot'] = AngieHelperSetup::cleanLiveSite($ret['wwwroot']);

		$this->configModel->set('wwwroot', $ret['wwwroot']);

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

		// Get the user IDs of users belonging to the SA groups
		try
		{
			$query = $db->getQuery(true)
						->select($db->qn('value'))
						->from($db->qn('#__config'))
						->where($db->qn('name') . ' = ' . $db->q('siteadmins'));
			$users = $db->setQuery($query)->loadResult();

			if (empty($users))
			{
				return $ret;
			}

			$userIDs = explode(',', $users);
		}
		catch (Exception $exc)
		{
			return $ret;
		}

		// Get the user information for the Administrator users
		try
		{
			$query = $db->getQuery(true)
						->select(array(
							$db->qn('id'),
							$db->qn('username'),
							$db->qn('email'),
						))
						->from($db->qn('#__user'))
						->where($db->qn('id'). ' IN(' . implode(',', $userIDs) . ')');
			$db->setQuery($query);

			$ret['superusers'] = $db->loadObjectList(0);
		}
		catch (Exception $exc)
		{
			return $ret;
		}

		return $ret;
	}

	/**
	 * Apply the settings to the configuration.php file and the database
	 */
	public function applySettings()
	{
		// Apply the Super Administrator changes
		$this->applySuperAdminChanges();

		// Get the state variables and update the global configuration
		$stateVars = $this->getStateVariables();
		// -- General settings
		$this->configModel->set('fullname', $stateVars->fullname);
		$this->configModel->set('shortname', $stateVars->shortname);
		$this->configModel->set('wwwroot', $stateVars->wwwroot);
		$this->configModel->set('dataroot', $stateVars->dataroot);

		// -- Database settings
		$connectionVars = $this->getDbConnectionVars();
		$this->configModel->set('dbtype', $connectionVars->dbtype);
		//$this->configModel->set('dblibrary', $stateVars->dblibrary);
		$this->configModel->set('dbhost', $connectionVars->dbhost);
		$this->configModel->set('dbuser', $connectionVars->dbuser);
		$this->configModel->set('dbpass', $connectionVars->dbpass);
		$this->configModel->set('dbname', $connectionVars->dbname);
		$this->configModel->set('dbprefix', $connectionVars->prefix);

		$this->configModel->saveToSession();

		// Get the config.php file and try to save it
		if (!$this->configModel->writeConfig(APATH_SITE . '/config.php'))
		{
			return false;
		}

		return true;
	}

	private function applySuperAdminChanges()
	{
        // Let's load the password compatibility file
        require_once APATH_ROOT.'/installation/framework/utils/password.php';

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
		$db = $this->getDatabase();

		// Create a new encrypted password
		$cryptpass = password_hash($password1, PASSWORD_DEFAULT);

		// Update the database record
		$query = $db->getQuery(true)
					->update($db->qn('#__user'))
					->set($db->qn('password') . ' = ' . $db->q($cryptpass))
					->set($db->qn('email') . ' = ' . $db->q($email))
					->where($db->qn('id') . ' = ' . $db->q($id));

		$db->setQuery($query)->execute();

		return true;
	}
}
