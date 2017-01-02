<?php

/**
 * @package   angi4j
 * @copyright Copyright (C) 2009-2017 Nicholas K. Dionysopoulos. All rights reserved.
 * @author    Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */
defined('_AKEEBA') or die();

class AngieModelWordpressSetup extends AngieModelBaseSetup
{
	/**
	 * Gets the basic site parameters
	 *
	 * @return  array
	 */
	protected function getSiteParamsVars()
	{
		$siteurl = str_replace('/installation/', '', AUri::root());
		$homeurl = str_replace('/installation/', '', AUri::root());

		$ret = array(
			'blogname'        => $this->getState('blogname', $this->configModel->get('blogname', 'Restored website')),
			'blogdescription' => $this->getState('blogdescription', $this->configModel->get('blogdescription', 'Restored website')),
			'dbcharset'       => $this->getState('dbcharset', $this->configModel->get('dbcharset', 'utf_8')),
			'dbcollation'     => $this->getState('dbcollation', $this->configModel->get('dbcollation', '')),
			'homeurl'         => $this->getState('homeurl', $homeurl),
			'siteurl'         => $this->getState('siteurl', $siteurl)
		);

        require_once APATH_INSTALLATION.'/angie/helpers/setup.php';

        $ret['homeurl'] = AngieHelperSetup::cleanLiveSite($ret['homeurl']);
        $ret['siteurl'] = AngieHelperSetup::cleanLiveSite($ret['siteurl']);

		$this->configModel->set('siteurl', $ret['siteurl']);
		$this->configModel->set('homeurl', $ret['homeurl']);

		// Special handling: if we were told to downgrade data from utf8mb4 to utf8 and the dbcharset or dbcollation
		// contains utf8mb4 we have to downgrade that too to utf8.
		/** @var AngieModelDatabase $dbModel */
		$dbModel       = AModel::getTmpInstance('Database', 'AngieModel');
		$allDbIni      = $dbModel->getDatabasesIni();
		$dbNames       = $dbModel->getDatabaseNames();
		$firstDb       = array_shift($dbNames);
		$dbIni         = $allDbIni[ $firstDb ];
		$dbOptions     = array(
			'driver'   => $dbIni['dbtype'],
			'database' => $dbIni['dbname'],
			'select'   => 0,
			'host'     => $dbIni['dbhost'],
			'user'     => $dbIni['dbuser'],
			'password' => $dbIni['dbpass'],
			'prefix'   => $dbIni['prefix'],
		);
		$db            = ADatabaseDriver::getInstance($dbOptions);
		$downgradeUtf8 = $dbIni['utf8tables'] && (
				!$dbIni['utf8mb4']
				|| ($dbIni['utf8mb4'] && !$db->supportsUtf8mb4())
			);

		if ($downgradeUtf8)
		{
			$ret['dbcharset']   = str_replace('utf8mb4', 'utf8', $ret['dbcharset']);
			$ret['dbcollation'] = str_replace('utf8mb4', 'utf8', $ret['dbcollation']);

			$this->configModel->set('dbcharset', $ret['dbcharset']);
			$this->configModel->set('dbcollation', $ret['dbcollation']);
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

		try
		{
			// Options are stored with the table prefix in front of it
			$table_prefix = $this->configModel->get('olddbprefix');

			// Deprecated value, but it's still used...
			$query = $db->getQuery(true)
				->select($db->qn('user_id'))
				->from($db->qn('#__usermeta'))
				->where($db->qn('meta_key') . ' = ' . $db->q($table_prefix . 'user_level'))
				->where($db->qn('meta_value') . ' = ' . $db->q(10));
			$deprecated = $db->setQuery($query)->loadColumn();

			// Current usage. Roles are stored as serialized arrays, so I have to get them all and check one by one
			$query = $db->getQuery(true)
				->select('*')
				->from($db->qn('#__usermeta'))
				->where($db->qn('meta_key') . ' = ' . $db->q($table_prefix . 'capabilities'));
			$users = $db->setQuery($query)->loadObjectList();

			$current = array();

			foreach ($users as $user)
			{
				$roles = unserialize($user->meta_value);

				if (isset($roles['administrator']) && $roles['administrator'])
				{
					$current[] = $user->user_id;
				}
			}

			$admins = array_intersect($current, $deprecated);
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
					$db->qn('ID') . ' AS ' . $db->qn('id'),
					$db->qn('user_login') . ' AS ' . $db->qn('username'),
					$db->qn('user_email').' AS '. $db->qn('email')
				))
				->from($db->qn('#__users'))
				->where($db->qn('ID') . ' IN(' . implode(',', $admins) . ')');
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
		$this->configModel->set('blogname', $stateVars->blogname);
		$this->configModel->set('blogdescription', $stateVars->blogdescription);
		$this->configModel->set('siteurl', $stateVars->siteurl);
		$this->configModel->set('homeurl', $stateVars->homeurl);

		// -- Database settings
		$connectionVars = $this->getDbConnectionVars();
		$this->configModel->set('dbtype', $connectionVars->dbtype);
		$this->configModel->set('dbhost', $connectionVars->dbhost);
		$this->configModel->set('dbuser', $connectionVars->dbuser);
		$this->configModel->set('dbpass', $connectionVars->dbpass);
		$this->configModel->set('dbname', $connectionVars->dbname);
		$this->configModel->set('dbprefix', $connectionVars->prefix);
		$this->configModel->set('dbcharset', $stateVars->dbcharset);
		$this->configModel->set('dbcollation', $stateVars->dbcollation);

		// -- Override the secret key
		$random = new AUtilsRandval();

		$this->configModel->set('auth_key', substr(base64_encode($random->generate(64)), 0, 64));
		$this->configModel->set('secure_auth_key', substr(base64_encode($random->generate(64)), 0, 64));
		$this->configModel->set('logged_in_key', substr(base64_encode($random->generate(64)), 0, 64));
		$this->configModel->set('nonce_key', substr(base64_encode($random->generate(64)), 0, 64));
		$this->configModel->set('auth_salt', substr(base64_encode($random->generate(64)), 0, 64));
		$this->configModel->set('secure_auth_salt', substr(base64_encode($random->generate(64)), 0, 64));
		$this->configModel->set('logged_in_salt', substr(base64_encode($random->generate(64)), 0, 64));
		$this->configModel->set('nonce_salt', substr(base64_encode($random->generate(64)), 0, 64));

		$this->configModel->saveToSession();

		// Sanity check
		if (!$stateVars->homeurl)
		{
			throw new Exception(AText::_('SETUP_HOMEURL_REQUIRED'));
		}

		if (!$stateVars->siteurl)
		{
			$this->configModel->set('siteurl', $stateVars->homeurl);
		}

		// Apply the Super Administrator changes
		$this->applySuperAdminChanges();

		// Get the wp-config.php file and try to save it
		if (!$this->configModel->writeConfig(APATH_SITE . '/wp-config.php'))
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
		$email = $this->getState('superuseremail', '');
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

		// Connect to the database
		$db = $this->getDatabase();

		// Create a new encrypted password. We are using the plain md5 since WP will update the hased
		// password the first time the user successfully logs in
		$crypt = md5($password1);

		// Update the database record
		$query = $db->getQuery(true)
			->update($db->qn('#__users'))
			->set($db->qn('user_pass') . ' = ' . $db->q($crypt))
			->set($db->qn('user_email') . ' = ' . $db->q($email))
			->where($db->qn('ID') . ' = ' . $db->q($id));
		$db->setQuery($query)->execute();

		return true;
	}
}