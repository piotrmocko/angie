<?php
/**
 * @package angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieViewSetup extends AView
{
	/** @var stdClass */
	public $stateVars;
	public $auto_prepend = array();
	public $hasAutoPrepend = false;

	public function onBeforeMain()
	{
		/** @var AngieModelWordpressSetup $model */
		$model           = $this->getModel();

		$this->stateVars 	  = $model->getStateVariables();
		$this->hasAutoPrepend = $model->hasAutoPrepend();

		// Prime the options array with some default info
		$this->auto_prepend = array(
			'checked'  => '',
			'disabled' => ''
		);

		// If we are restoring to a new server everything is checked by default
		if ($model->isNewhost())
		{
			$this->auto_prepend['checked'] = 'checked="checked"';
		}

		// If any option is not valid (ie missing files) we gray out the option AND remove the check
		// to avoid user confusion
		if (!$this->hasAutoPrepend)
		{
			$this->auto_prepend['checked']  = '';
			$this->auto_prepend['disabled'] = 'disabled="disabled"';
		}

		return true;
	}
}
