<?php
/**
 * @package angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieControllerOctobercmsMain extends AngieControllerBaseMain
{
	/**
	 * Try to read app/system/config.php
	 */
	public function getconfig()
	{
		// Load the default configuration and save it to the session
		$data   = $this->input->getData();

		/** @var AngieModelOctobercmsConfiguration $model */
        $model = AModel::getAnInstance('Configuration', 'AngieModel', array(), $this->container);
        $this->input->setData($data);
        $this->container->session->saveData();

		$vars = $model->loadFromFile();

		if ($vars)
		{
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
}
