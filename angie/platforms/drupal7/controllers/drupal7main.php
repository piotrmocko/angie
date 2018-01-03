<?php
/**
 * @package angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieControllerDrupal7Main extends AngieControllerBaseMain
{
	/**
	 * Try to read settings.php
	 */
	public function getconfig()
	{
		// Load the default configuration and save it to the session
		$data = $this->input->getData();

        /** @var AngieModelBaseConfiguration $model */
		$model = AModel::getAnInstance('Configuration', 'AngieModel', array(), $this->container);
		$this->input->setData($data);
		$this->container->session->saveData();

		// Try to load the configuration from the site's configuration.php
		$filename = APATH_SITE . '/sites/default/settings.php';
		if (file_exists($filename))
		{
			$vars = $model->loadFromFile($filename);
			foreach ($vars as $k => $v)
			{
				$model->set($k, $v);
			}
			$this->container->session->saveData();

			echo json_encode(true);
		}
		else
		{
			echo json_encode(false);
		}
	}

    /**
     * Is this a multisite installation?
     */
    public function ismultisite()
    {
        /** @var AngieModelDrupal7Configuration $configModel */
        $configModel = AModel::getAnInstance('Configuration', 'AngieModel');
        $folders = $configModel->getSettingsFolders();

        // If I have more than a folder containing the settings.php file it means that this is
        // a multisite installation
        if(count($folders) > 1)
        {
            echo json_encode(true);
        }
        else
        {
            echo json_encode(false);
        }
    }
}
