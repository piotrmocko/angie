<?php
/**
 * @package angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class PlatformSteps
{
	/**
	 * Adds additional steps for this installer
	 *
	 * @param array $steps
	 *
	 * @return mixed
	 */
	public function additionalSteps(array $steps)
	{
        // Let's double check that we really have the sites folder
        if(!is_dir(APATH_ROOT.'/sites'))
        {
            return $steps;
        }

        /** @var AngieModelDrupal8Configuration $configModel */
        $configModel = AModel::getAnInstance('Configuration', 'AngieModel');
        /** @var AngieModelDrupal8Setup $setupModel */
        $setupModel = AModel::getAnInstance('Setup', 'AngieModel');

        // Do I have a multi-site environment? If so I have to display the setup page several times
        $extraSetup  = array();
        $directories = $configModel->getSettingsFolders();

        // We have to update
        foreach($directories as $directory)
        {
            // Skip the default directory
            if($directory == 'default')
            {
                continue;
            }

            // Wait, before adding such directory to the stack, I have to update them with the new domain name
            // ie from oldsite.local.slave to newsite.com.slave
            $directory = $setupModel->updateSlaveDirectory(APATH_ROOT.'/sites/'.$directory);

            $extraSetup[] = basename($directory);
        }

        if($extraSetup)
        {
            // I have to manually add the default "setup"
            $steps['setup'][] = 'default';

            // Now I can add the extra steps
            $steps['setup'] = array_merge($steps['setup'], $extraSetup);
        }

        // Do I have an alias file? If so let's add the step so the user can change it
        if(file_exists(APATH_ROOT.'/sites/sites.php'))
        {
            $finalise = array_pop($steps);
            $steps['updatealias'] = null;
            $steps['finalise'] = $finalise;
        }

        return $steps;
	}
}
