<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieModelMauticFinalise extends AngieModelBaseFinalise
{
	public function cleanup()
	{
        // First of all let's be sure that the cache path is empty, otherwise Doctrine would load the previous cached version
        $this->recursive_remove_directory(APATH_ROOT.'/app/cache/prod');

        $result = parent::cleanup();

		return $result;
	}
}