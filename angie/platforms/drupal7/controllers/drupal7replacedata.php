<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieControllerDrupal7Replacedata extends AngieControllerBaseReplacedata
{
	protected function checkReplaceNeeded()
	{
		// Drupal 7 doesn't store the URL inside the database, but inside the article the
		// absolute URL could be used. This means that we really don't know if we're restoring
		// in the same domain and folder, so let's always return true and ask the user to supply
		// those information
		return true;
	}
}