<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
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
}