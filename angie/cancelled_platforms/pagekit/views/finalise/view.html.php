<?php
/**
 * @package angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieViewFinalise extends AView
{
	public $showconfig;
	public $configuration;

	public function onBeforeMain()
	{
		$model = $this->getModel();

		$this->showconfig = $model->getState('showconfig', 0);

		if ($this->showconfig)
		{
			/** @var AngieModelPagekitConfiguration $configModel */
			$configModel = AModel::getAnInstance('Configuration', 'AngieModel', array(), $this->container);

			$this->configuration = $configModel->getFileContents();
		}

		return true;
	}
}
