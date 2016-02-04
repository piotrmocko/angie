<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
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

		// Do I have a multi-site environment? If so I have to display the setup page several times
        $iterator    = new DirectoryIterator(APATH_ROOT.'/sites');
        $directories = array();
        $extraSetup  = array();

        // First of all let's get all the directories. I have to exclude the "all" one since there could be a massive
        // amount of files/directories
        foreach($iterator as $file)
        {
            if($file->isDot() || !$file->isDir())
            {
                continue;
            }

            // Let's skip the "all" and "default" one, additional sites can't be there
            if($file->getFilename() == 'all' || $file->getFilename() == 'default')
            {
                continue;
            }

            $directories[] = $file->getPathname();
        }

        foreach($directories as $directory)
        {
            $iterator = new DirectoryIterator($directory);

            foreach($iterator as $file)
            {
                if($file->getFilename() != 'settings.php')
                {
                    continue;
                }

                $extraSetup[] = basename($file->getPath());
            }
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