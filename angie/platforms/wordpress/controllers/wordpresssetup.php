<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2017 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieControllerWordpressSetup extends AngieControllerBaseSetup
{
    /**
     * I have to override parent method since I have the replacedata extra step
     *
     * @throws AExceptionApp
     */
	public function apply()
	{
		/** @var AngieModelWordpressSetup $model */
		$model = $this->getThisModel();

		try
		{
			$writtenConfiguration = $model->applySettings();
			$msg = null;
			$this->container->session->set('writtenConfiguration', $writtenConfiguration);

			$url = 'index.php?view=replacedata';
		}
		catch (Exception $exc)
		{
			$msg = $exc->getMessage();
			$url = 'index.php?view=setup';
		}

		$this->container->session->saveData();

		$this->setRedirect($url, $msg, 'error');

        // Encode the result if we're in JSON format
        if($this->input->getCmd('format', '') == 'json')
        {
            $result['error'] = $msg;

            echo json_encode($result);
        }
	}
}