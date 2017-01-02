<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2017 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieControllerWordpressFinalise extends AngieControllerBaseFinalise
{
	public function ajax()
	{
		$method = $this->input->getCmd('method', '');
		$result = false;
		$model = $this->getThisModel();

		if (method_exists($model, $method))
		{
			try
			{
				$result = $model->$method();
			}
			catch(Exception $e)
			{
				$result = false;
			}
		}

		echo json_encode($result);
	}
}