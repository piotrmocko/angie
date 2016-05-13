<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieControllerWordpressReplacedata extends AngieControllerBaseReplacedata
{
	protected function checkReplaceNeeded()
	{
		/** @var AngieModelWordpressConfiguration $config */
		$config  = AModel::getAnInstance('Configuration', 'AngieModel', array(), $this->container);
		$result  = true;

		// These values are stored inside the session, after the setup step
		$old_url = $config->get('oldurl');
		$new_url = $config->get('homeurl');

		// If we are restoring to the same URL we don't need to replace any data
		if ($old_url == $new_url)
		{
			$result = false;
		}

		return $result;
	}
}