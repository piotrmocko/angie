<?php
/**
 * @package angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class PlatformSteps
{
	/**
	 * Adds additional steps for this installer
	 *
	 * @param array $steps
	 *
	 * @return mixed
	 */
	public function additionalSteps(array $steps)
	{
		$finalise = array_pop($steps);
		$steps['replacedata'] = null;
		$steps['finalise'] = $finalise;

		return $steps;
	}
}
