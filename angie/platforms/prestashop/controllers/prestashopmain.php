<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieControllerPrestashopMain extends AngieControllerBaseMain
{
	/**
	 * Try to read configuration
	 */
	public function getconfig()
	{
		// Load the default configuration and save it to the session
		$data   = $this->input->getData();

        /** @var AngieModelPrestashopConfiguration $model */
		$model  = AModel::getAnInstance('Configuration', 'AngieModel');

		$this->input->setData($data);

		ASession::getInstance()->saveData();

		// Try to load the configuration from the site's configuration
		$filename = APATH_SITE . '/config/settings.inc.php';
		if (file_exists($filename))
		{
			$vars = $model->loadFromFile($filename);

			foreach ($vars as $k => $v)
			{
				$model->set($k, $v);
			}

			ASession::getInstance()->saveData();

			echo json_encode(true);
		}
		else
		{
			echo json_encode(false);
		}
	}
}