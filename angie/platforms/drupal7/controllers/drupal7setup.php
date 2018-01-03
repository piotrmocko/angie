<?php
/**
 * @package angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieControllerDrupal7Setup extends AngieControllerBaseSetup
{
	public function applyjson()
    {
        // We have to use the HTML filter, since the key could contain a forward slash
        // e.g. virtual_folders/first_folder
        $key = $this->input->getCmd('substep', 'default', 'html');

        if (empty($key))
        {
            $result = array(
                'percent'	 => 0,
                'error'		 => AText::_('OFFSITEDIRS_ERR_INVALIDKEY'),
                'done'		 => 1,
                'showconfig' => ''
            );
            echo json_encode($result);
            return;
        }

        try
        {
            /** @var AngieModelDrupal7Setup $model */
            $model  = $this->getThisModel();
            $config = '';
            $error  = '';

            $writtenConfiguration = $model->applySettings($key);

            if(!$writtenConfiguration)
            {
                /** @var AngieModelDrupal7Configuration $configModel */
                $configModel = AModel::getAnInstance('Configuration', 'AngieModel', array(), $this->container);
                $config      = $configModel->getFileContents(APATH_SITE . '/sites/'.$key.'/settings.php');
                $error       = AText::_('FINALISE_LBL_CONFIGINTRO').'<br/>'.AText::_('FINALISE_LBL_CONFIGOUTRO');
            }

            $result = array(
                'percent'	 => 100,
                'error'		 => $error,
                'done'		 => 1,
                'showconfig' => $config
            );
        }
        catch (Exception $exc)
        {
            $result = array(
                'percent'	 => 0,
                'error'		 => $exc->getMessage(),
                'done'		 => 1,
                'showconfig' => ''
            );
        }

        echo json_encode($result);
    }

    /**
     * This method allows to update the slave directories with the new hostname. It's never invoked inside ANGIE,
     * it's only used by UNiTE
     */
    public function updateslavedirectories()
    {
        /** @var AngieModelDrupal7Configuration $configModel */
        $configModel = AModel::getAnInstance('Configuration', 'AngieModel');
        /** @var AngieModelDrupal7Setup $setupModel */
        $setupModel = AModel::getAnInstance('Setup', 'AngieModel');

        $host = $this->input->getString('host', 'SERVER');

        // Do I have a multi-site environment? If so I have to display the setup page several times
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
            $setupModel->updateSlaveDirectory(APATH_ROOT.'/sites/'.$directory, $host);
        }

        echo json_encode(true);
    }
}
