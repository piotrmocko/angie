<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

abstract class AngieControllerBaseReplacedata extends AController
{
	public function main()
	{
		// If we are restoring to the same URL we don't need to replace any data
		if (!$this->checkReplaceNeeded())
		{
			$this->setRedirect('index.php?view=finalise');

			return;
		}

		parent::main();
	}

	/**
	 * Checks if we really have to perform a replacement or not (ie we moved to a new domain or not)
	 * 
	 * @return bool
	 */
	abstract protected function checkReplaceNeeded();


	public function ajax()
	{
		$method = $this->input->getCmd('method', '');
		$result = false;

		/** @var AngieModelBaseReplacedata $model */
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
				$result = array('error' => $e->getMessage(), 'msg' => 'Error ' . $e->getCode() . ': ' . $e->getMessage(), 'more' => false);
			}
		}

		$model->saveEngineStatus();

        $this->container->session->saveData();

		echo json_encode($result);
	}

    public function replaceneeded()
    {
	    $result = $this->checkReplaceNeeded();

	    echo json_encode($result);
    }
}