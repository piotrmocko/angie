<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieControllerWordpressReplacedata extends AController
{
	public function main()
	{
		/** @var AngieModelWordpressConfiguration $config */
		$config  = AModel::getAnInstance('Configuration', 'AngieModel');

		// These values are stored inside the session, after the setup step
		$old_url = $config->get('oldurl');
		$new_url = $config->get('homeurl');

		// If we are restoring to the same URL we don't need to replace any data
		if ($old_url == $new_url)
		{
			$this->setRedirect('index.php?view=finalise');

			return;
		}

		parent::main();
	}

	public function ajax()
	{
		$method = $this->input->getCmd('method', '');
		$result = false;

		/** @var AngieModelWordpressReplacedata $model */
		$model = $this->getThisModel();

		$model->loadEngineStatus();

		if (method_exists($model, $method))
		{
			try
			{
				$result = $model->$method();
			}
			catch(Exception $e)
			{
				$result = array('msg' => 'Error ' . $e->getCode() . ': ' . $e->getMessage(), 'more' => false);
			}
		}

		$model->saveEngineStatus();

		AApplication::getInstance()->session->saveData();

		@ob_end_clean();
		echo '###'.json_encode($result).'###';

		AApplication::getInstance()->close();
	}
}