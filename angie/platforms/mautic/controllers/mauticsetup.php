<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieControllerMauticSetup extends AngieControllerBaseSetup
{
    /**
     * I have to override parent method since I have the replacedata extra step
     *
     * @throws AExceptionApp
     */
	public function apply()
	{
        /** @var AngieModelMauticSetup $model */
		$model = $this->getThisModel();
		try
		{
			$writtenConfiguration = $model->applySettings();
			$msg = null;
            AApplication::getInstance()->session->set('writtenConfiguration', $writtenConfiguration);

            $url = 'index.php?view=replacedata';
		}
		catch (Exception $exc)
		{
			$msg = $exc->getMessage();
			$url = 'index.php?view=setup';
		}

        AApplication::getInstance()->session->saveData();

		$this->setRedirect($url, $msg, 'error');
	}
}