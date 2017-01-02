<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2017 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieViewUpdatealias extends AView
{
    /** @var    array  Current aliases read from the sites.php file */
    public $aliases;

	public function onBeforeMain()
	{
        /** @var AngieModelUpdatealias $model */
        $model = $this->getModel();
		$this->aliases = $model->readAliases();

		return true;
	}
}