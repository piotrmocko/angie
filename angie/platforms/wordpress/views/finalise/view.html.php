<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2017 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieViewFinalise extends AView
{
	public function onBeforeMain()
	{
        $this->container->application->getDocument()->addScript('platform/js/finalise_wp.js');

		$model = $this->getModel();

		$writtenConfiguration = $this->container->session->get('writtenConfiguration', true);
		$this->showconfig = !$writtenConfiguration;

		if ($this->showconfig)
		{
			/** @var AngieModelWordpressConfiguration $configurationModel */
			$configurationModel = AModel::getAnInstance('Configuration', 'AngieModel', array(), $this->container);
			$this->configuration = $configurationModel->getFileContents();
		}

		return true;
	}
}