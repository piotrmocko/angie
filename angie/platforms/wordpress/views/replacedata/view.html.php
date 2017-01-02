<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2017 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieViewReplacedata extends AView
{
	public function onBeforeMain()
	{
		$this->container->application->getDocument()->addScript('platform/js/replacedata.js');

		/** @var AngieModelWordpressReplacedata $model */
		$model = $this->getModel();

		$this->replacements = $model->getReplacements();
		$this->otherTables = $model->getNonCoreTables();

		return true;
	}
}