<?php

/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */
defined('_AKEEBA') or die();

class AngieModelMauticSetup extends AngieModelBaseSetup
{
	/**
	 * Gets the basic site parameters
	 *
	 * @return  array
	 */
	protected function getSiteParamsVars()
	{
		$defaultTmpPath	 = APATH_ROOT . '/tmp';
		$defaultLogPath	 = APATH_ROOT . '/log';

		$ret = array(
			'siteemail'		 => $this->getState('siteemail', $this->configModel->get('mailfrom', 'no-reply@example.com')),
			'emailsender'	 => $this->getState('emailsender', $this->configModel->get('fromname', 'Restored website')),
			'livesite'		 => $this->getState('livesite', ''),
			'logspath'		 => $this->getState('logspath', $this->configModel->get('log_path', $defaultLogPath)),
			'default_tmp'	 => $defaultTmpPath,
			'default_log'	 => $defaultLogPath,
			'site_root_dir'	 => APATH_ROOT,
		);

        // Let's cleanup the live site url
        require_once APATH_INSTALLATION.'/angie/helpers/setup.php';

        $ret['livesite'] = AngieHelperSetup::cleanLiveSite($ret['livesite']);

		// I can't check if the logspath is writable since it will use some tokens inside it

		return $ret;
	}

	protected function getSuperUsersVars()
	{
		$ret = array();

		// Connect to the database
		$connectionVars = $this->getDbConnectionVars();
		try
		{
			$name = $connectionVars->dbtype;
			$options = array(
				'database'	 => $connectionVars->dbname,
				'select'	 => 1,
				'host'		 => $connectionVars->dbhost,
				'user'		 => $connectionVars->dbuser,
				'password'	 => $connectionVars->dbpass,
				'prefix'	 => $connectionVars->prefix,
				//'port'				=> $connectionVars->dbport,
			);
			$db		 = ADatabaseFactory::getInstance()->getDriver($name, $options);
		}
		catch (Exception $exc)
		{
			return $ret;
		}

		// Find the Super User groups
		try
		{
			$query = $db->getQuery(true)
                        ->select($db->qn('id'))
                        ->from($db->qn('#__roles'))
                        ->where($db->qn('is_admin') . ' = ' . $db->q(1));
			$roles = $db->setQuery($query)->loadColumn();

			if (empty($roles))
			{
				return $ret;
			}
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
					$db->qn('username'),
					$db->qn('email'),
				))->from($db->qn('#__users'))
				->where($db->qn('role_id'). ' IN(' . implode(',', $roles) . ')');

			$ret['superusers'] = $db->setQuery($query)->loadObjectList(0);
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

        $livesite = trim($stateVars->livesite, '/');

        // The live_site variable is required
        if(!$livesite)
        {
            throw new Exception(AText::_('ANGIE_MAUTIC_LIVESITE_REQUIRED'));
        }

        if(strpos('http', $livesite) === false)
        {
            $livesite = 'http://'.$livesite;
        }

		// -- General settings
		$this->configModel->set('mailfrom', $stateVars->siteemail);
		$this->configModel->set('fromname', $stateVars->emailsender);
		$this->configModel->set('live_site', $livesite);
		$this->configModel->set('log_path', $stateVars->logspath);

		// -- Database settings
		$connectionVars = $this->getDbConnectionVars();
		$this->configModel->set('dbtype', $connectionVars->dbtype);
		$this->configModel->set('host', $connectionVars->dbhost);
		$this->configModel->set('user', $connectionVars->dbuser);
		$this->configModel->set('password', $connectionVars->dbpass);
		$this->configModel->set('db', $connectionVars->dbname);
		$this->configModel->set('dbprefix', $connectionVars->prefix);

		// -- Override the secret key
		$this->configModel->set('secret', $this->genRandomPassword(64));

		$this->configModel->saveToSession();

		// Get the configuration.php file and try to save it
		$configurationPHP = $this->configModel->getFileContents();
		$filepath = APATH_SITE . '/app/config/local.php';

        if(!$configurationPHP)
        {
            return false;
        }

		if (! @file_put_contents($filepath, $configurationPHP))
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

        // Let's load the password compatibility file
        require_once APATH_ROOT.'/installation/framework/utils/password.php';

		// Connect to the database
		$connectionVars = $this->getDbConnectionVars();
		$name = $connectionVars->dbtype;
		$options = array(
			'database'	 => $connectionVars->dbname,
			'select'	 => 1,
			'host'		 => $connectionVars->dbhost,
			'user'		 => $connectionVars->dbuser,
			'password'	 => $connectionVars->dbpass,
			'prefix'	 => $connectionVars->prefix,
			//'port'				=> $connectionVars->dbport,
		);
		$db		 = ADatabaseFactory::getInstance()->getDriver($name, $options);

		// Create a new encrypted password, at the moment (July 2015) Mautic is using a cost of 13
        $cryptpass = password_hash($password1, PASSWORD_BCRYPT, array('cost' => 13));

		// Update the database record
		$query = $db->getQuery(true)
			->update($db->qn('#__users'))
			->set($db->qn('password') . ' = ' . $db->q($cryptpass))
			->set($db->qn('email') . ' = ' . $db->q($email))
			->where($db->qn('id') . ' = ' . $db->q($id));
		$db->setQuery($query);
		$db->execute();

		return true;
	}

	private function genRandomPassword($length = 8)
	{
		$salt = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$len = strlen($salt);
		$makepass = '';

		$stat = @stat(__FILE__);
		if(empty($stat) || !is_array($stat)) $stat = array(php_uname());

		mt_srand(crc32(microtime() . implode('|', $stat)));

		for ($i = 0; $i < $length; $i ++) {
			$makepass .= $salt[mt_rand(0, $len -1)];
		}

		return $makepass;
	}
}