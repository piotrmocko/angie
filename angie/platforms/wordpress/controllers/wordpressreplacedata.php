<?php
/**
 * @package angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

use Akeeba\Replace\Engine\ErrorHandling\WarningException;

defined('_AKEEBA') or die();

class AngieControllerWordpressReplacedata extends AController
{
	public function main()
	{
		/** @var AngieModelWordpressConfiguration $config */
		$config  = AModel::getAnInstance('Configuration', 'AngieModel', array(), $this->container);

		// These values are stored inside the session, after the setup step
		$old_url = $config->get('oldurl');
		$new_url = $config->get('homeurl');

		// If we are restoring to the same URL we don't need to replace any data
		if ($old_url == $new_url)
		{
			$this->setRedirect('index.php?view=finalise');

			return;
		}

		if ($this->input->getBool('force', false))
		{
			$session      = $this->container->session;
			$session->set('dataReplacements', null);
		}

		parent::main();
	}

	public function ajax()
	{
		/** @var AngieModelWordpressReplacedata $model */
		$model = $this->getThisModel();
		$method = $this->input->getCmd('method', '');

		try
		{
			switch ($method)
			{
				case 'init':
					// First we need to update the multisite tables, if necessary.
					if ($model->isMultisite())
					{
						$model->updateMultisiteTables();
					}

					$status = $model->init();
					break;

				case 'step':
					$status = $model->step();
					break;
			}

			$this->container->session->saveData();

			$error    = $status->getError();
			$warnings = $status->getWarnings();
			$hasError = is_object($error) && ($error instanceof Exception);

			$result            = [
				'error' => $hasError ? $error->getMessage() : '',
				'msg'   => $status->getDomain() . ' ' . $status->getStep() . ' ' . $status->getSubstep(),
				'more'  => !$status->isDone() && !$hasError,
				'warnings' => array_map(function (WarningException $w) {
					return $w->getMessage();
				}, $warnings)
			];

			if ($hasError)
			{
				$result['msg'] = $error->getCode() . ': ' . $error->getMessage();
			}

			// Perform finalization steps (file data replacement when we're done)
			if ($status->isDone())
			{
				$model->updateFiles();
				$model->updateWPConfigFile();
			}
		}
		catch (Exception $e)
		{
			$result = [
				'error'    => $e->getMessage(),
				'msg'      => $e->getCode() . ': ' . $e->getMessage(),
				'more'     => false,
				'warnings' => [],
			];
		}

		echo json_encode($result);
	}

    public function replaceneeded()
    {
        /** @var AngieModelWordpressConfiguration $config */
        $config  = AModel::getAnInstance('Configuration', 'AngieModel', array(), $this->container);
        $result  = true;

        // These values are stored inside the session, after the setup step
        $old_url = $config->get('oldurl');
        $new_url = $config->get('siteurl');

        // If we are restoring to the same URL we don't need to replace any data
        if ($old_url == $new_url)
        {
            $result = false;
        }

        echo json_encode($result);
    }
}
