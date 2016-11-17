<?php

/**
 * @package   angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author    Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */
defined('_AKEEBA') or die();

class AngieModelGravSetup extends AngieModelBaseSetup
{
	/**
	 * Gets the basic site parameters
	 *
	 * @return  array
	 */
	protected function getSiteParamsVars()
	{
		$ret = array(
			'sitename' => $this->getState('sitename', $this->configModel->get('sitename', 'Restored website')),
		);

		return $ret;
	}

	protected function getSuperUsersVars()
	{
		$ret = array();

		// Each account is stored inside a single the user/accounts directory
		// However this is an optional module, so we could have no users to update

		if (!is_dir(APATH_ROOT. '/user/accounts'))
		{
			return $ret;
		}

		$iterator = new DirectoryIterator(APATH_ROOT . '/user/accounts');
		$i = 0;

		foreach ($iterator as $file)
		{
			if ($file->isDot() || $file->isDir())
			{
				continue;
			}

			if ($file->getExtension() != 'yaml')
			{
				continue;
			}

			$user = \Symfony\Component\Yaml\Yaml::parse($file->getPathname());

			// Sanity checks on array structure
			if (!isset($user['access']) || !isset($user['access']['admin']) || !isset($user['access']['admin']['super']))
			{
				continue;
			}

			// I want only super admins
			if (!$user['access']['admin']['super'])
			{
				continue;
			}

			$i++;

			$ret['superusers'][] = (object) array(
				'id' => $i,
				'username' => $user['fullname'],
				'email' => $user['email']
			);
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

		$this->configModel->saveToSession();

		// Apply the Super Administrator changes
		$this->applySuperAdminChanges();

		// Get the wp-config.php file and try to save it
		if (!$this->configModel->writeConfig(APATH_SITE . '/user/config/site.yaml'))
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

		// Create a new bCrypt-bashed password. At the time of this writing (July 2015) PageKit is using a cost of 10
		$cryptpass = password_hash($password1, PASSWORD_BCRYPT, array('cost' => 10));

		// Update the database record
		$db = $this->getDatabase();

		$query = $db->getQuery(true)
			->update($db->qn('#__system_user'))
			->set($db->qn('password') . ' = ' . $db->q($cryptpass))
			->set($db->qn('email') . ' = ' . $db->q($email))
			->where($db->qn('id') . ' = ' . $db->q($id));
		$db->setQuery($query)->execute();

		return true;
	}
}