<?php

/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */
defined('_AKEEBA') or die();

class AngieModelDrupal8Setup extends AngieModelBaseSetup
{
    /**
     * @var AngieModelDrupal8Configuration $configModel
     */
    protected $configModel;

	/**
	 * Gets the basic site parameters
	 *
	 * @return  array
	 */
	protected function getSiteParamsVars()
	{
        $key      = $this->input->getCmd('substep', 'default');
        $fieldKey = str_replace('.', '_', $key);

		$defaultTmpPath	 = APATH_ROOT . '/tmp';
		$defaultLogPath	 = APATH_ROOT . '/log';

		$ret = array(
			'sitename'		 => $this->getState($fieldKey.'_sitename', $this->configModel->get('sitename', 'Restored website', $key)),
			'siteemail'		 => $this->getState($fieldKey.'_siteemail', $this->configModel->get('site_mail', 'no-reply@example.com', $key)),
			'cookiedomain'	 => $this->getState($fieldKey.'_cookiedomain', $this->configModel->get('cookie_domain', '', $key)),
            'livesite'		 => $this->getState($fieldKey.'_livesite', $this->configModel->get('live_site', '', $key)),
			'tmppath'		 => $this->getState($fieldKey.'_tmppath', $this->configModel->get('tmp_path', $defaultTmpPath, $key)),
			'default_tmp'	 => $defaultTmpPath,
			'default_log'	 => $defaultLogPath,
			'site_root_dir'	 => APATH_ROOT,
		);

        // Let's cleanup the live site url
        require_once APATH_INSTALLATION.'/angie/helpers/setup.php';

        $ret['livesite'] = AngieHelperSetup::cleanLiveSite($ret['livesite']);

		// Deal with tmp and logs path
		if (!@is_dir($ret['tmppath']))
		{
			$ret['tmppath'] = $defaultTmpPath;
		}
		elseif (!@is_writable($ret['tmppath']))
		{
			$ret['tmppath'] = $defaultTmpPath;
		}

		return $ret;
	}

    protected function getSuperUsersVars()
	{
		$ret = array();
        $key = $this->input->getCmd('substep', 'default');

		// Connect to the database
		try
		{
			$db = $this->getDatabase($key);
		}
		catch (Exception $exc)
		{
			return $ret;
		}

		// Get the user IDs of users belonging to the SA groups
		try
		{
            $query = $db->getQuery(true)
                        ->select($db->qn('entity_id'))
                        ->from($db->qn('#__user__roles'))
                        ->where($db->qn('roles_target_id') . ' = ' . $db->q('administrator'));
			$rawUserIDs = $db->setQuery($query)->loadColumn(0);

			if (empty($rawUserIDs))
			{
				return $ret;
			}

			$userIDs = array();

			foreach ($rawUserIDs as $id)
			{
				$userIDs[] = $db->q($id);
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
                            $db->qn('uid').' AS id',
                            $db->qn('name').' AS username',
                            $db->qn('mail').' AS email',
                        ))
                        ->from($db->qn('#__users_field_data'))
                        ->where($db->qn('uid'). ' IN(' . implode(',', $userIDs) . ')');

			$ret['superusers'] = $db->setQuery($query)->loadObjectList();
		}
		catch (Exception $exc)
		{
			return $ret;
		}

		return $ret;
	}

	/**
	 * Apply the settings to the configuration.php file and the database
     *
     * @param   string  $folder     Folder containing the settings.php file
     *
     * @return  bool    I was able to write the configuration file?
	 */
	public function applySettings($folder = '')
	{
        if(!$folder)
        {
            $folder = 'default';
        }

        // TODO Should we clean the folder path to avoid relative-paths exploits?
        $folder = trim($folder, " \t\n\r\0\x0B".DIRECTORY_SEPARATOR);

		// Get the state variables and update the global configuration
		$stateVars = $this->getStateVariables();
		// -- General settings
		$this->configModel->set('sitename', $stateVars->sitename, $folder);
		$this->configModel->set('site_mail', $stateVars->siteemail, $folder);
        $this->configModel->set('tmp_path', $stateVars->tmppath, $folder);

        // I have to save the old live_site and cookie domain value: if it was previously defined and now it's not
        // I have to comment some code inside the settings file
        $this->configModel->set('old_cookie_domain', $this->configModel->get('cookie_domain', '', $folder), $folder);
        $this->configModel->set('old_live_site', $this->configModel->get('live_site', '', $folder), $folder);

		$this->configModel->set('cookie_domain', $stateVars->cookiedomain, $folder);
        $this->configModel->set('live_site', $stateVars->livesite, $folder);

		// -- Database settings
		$connectionVars = $this->configModel->getDatabase($folder);

		$this->configModel->set('dbtype', $connectionVars->dbtype, $folder);
		$this->configModel->set('host', $connectionVars->dbhost, $folder);
		$this->configModel->set('user', $connectionVars->dbuser, $folder);
		$this->configModel->set('password', $connectionVars->dbpass, $folder);
		$this->configModel->set('db', $connectionVars->dbname, $folder);
		$this->configModel->set('dbprefix', $connectionVars->prefix, $folder);

        // -- Override the secret key
        $random = new AUtilsRandval();

		$this->configModel->set('drupal_private_key', str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($random->generate(55))), $folder);
		$this->configModel->set('cron_key', str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($random->generate(55))), $folder);

		$this->configModel->saveToSession();

        // Apply the Super Administrator changes
        $this->applySuperAdminChanges();

        // Get the wp-config.php file and try to save it
        if (!$this->configModel->writeConfig(APATH_SITE . '/sites/'.$folder.'/settings.php'))
        {
            return false;
        }

		return true;
	}

	private function applySuperAdminChanges()
	{
        $key = $this->input->getCmd('substep', 'default');
        $fieldKey = str_replace('.', '_', $key);

		// Get the Super User ID. If it's empty, skip.
		$id = $this->getState($fieldKey.'_superuserid', 0);
		if (!$id)
		{
			return false;
		}

		// Get the Super User email and password
		$email     = $this->getState($fieldKey.'_superuseremail', '');
		$password1 = $this->getState($fieldKey.'_superuserpassword', '');
		$password2 = $this->getState($fieldKey.'_superuserpasswordrepeat', '');

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
		$db = $this->getDatabase($key);

        require_once APATH_ROOT.'/installation/framework/utils/PasswordHash.php';

        // Let's use phpass to create the new password. Drupal will convert it in his own hash
        // We have to do that since Drupal is streching the standard sha512 hash doing a lot of loops,
        // doing that would require to rewrite a lot of code
        $hasher = new PasswordHash(10, true);
        $cryptpass = $hasher->HashPassword($password1);

		// Update the database record
		$query = $db->getQuery(true)
                    ->update($db->qn('#__users_field_data'))
                    ->set($db->qn('pass') . ' = ' . $db->q($cryptpass))
                    ->set($db->qn('mail') . ' = ' . $db->q($email))
                    ->where($db->qn('uid') . ' = ' . $db->q($id));
		$db->setQuery($query)->execute();

		return true;
	}

    /**
     * I have to override the parent method since Drupal support multi site installation
     *
     * @param null $key
     *
     * @return ADatabaseDriver
     */
    protected function getDatabase($key = null)
    {
        $connectionVars = $this->configModel->getDatabase($key);

        $name = $connectionVars->dbtype;
        $options = array(
            'database'	 => $connectionVars->dbname,
            'select'	 => 1,
            'host'		 => $connectionVars->dbhost,
            'user'		 => $connectionVars->dbuser,
            'password'	 => $connectionVars->dbpass,
            'prefix'	 => $connectionVars->prefix
        );

        $db = ADatabaseFactory::getInstance()->getDriver($name, $options);

        return $db;
    }
}