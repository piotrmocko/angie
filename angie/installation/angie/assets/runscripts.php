<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class RunScripts extends JApplicationWeb
{
    public function doExecute()
    {
        // The script file requires an installer instance, however it's not used inside the code...
        $installer  = JInstaller::getInstance();
        $scriptFile = JPATH_ROOT.'/administrator/components/com_admin/script.php';

        if (!is_file($scriptFile))
        {
            return;
        }

        include_once $scriptFile;

        $classname = 'JoomlaInstallerScript';

        if (!class_exists($classname))
        {
            return;
        }

        $manifestClass = new $classname();

        if ($manifestClass && method_exists($manifestClass, 'update'))
        {
	        // At the moment (Joomla 3.5.1) the update will fail since Joomla will try to clear its cache. In order
	        // to do so, it requests the Application some states of the model. Since there is no "Joomla application"
	        // an exception is raised. Let's catch it to avoid some unpleasant error message
	        try
	        {
		        $manifestClass->update($installer);
	        }
	        catch (Exception $e)
	        {
		        // Don't cry if it fails
	        }
        }
    }
}