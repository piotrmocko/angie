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
	public $disable_wordfence = array();

	public function onBeforeMain()
	{
		$this->stateVars = $this->getModel()->getStateVariables();

		// Prime the options array with some default info
		$this->disable_wordfence = array(
			'checked'  => '',
			'disabled' => '',
			'help'     => 'SETUP_LBL_SERVERCONFIG_WORDFENCE_HELP'
		);

		return true;
	}
}
