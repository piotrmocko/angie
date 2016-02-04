<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieModelMagentoFinalise extends AngieModelBaseFinalise
{
	/**
	 * Post-restoration cleanup. Renames files and removes the installation directory.
	 *
	 * @return bool
	 */
	public function cleanup()
	{
        // Delete the cache directory, too
        $result = $this->recursive_remove_directory(APATH_ROOT.'/var/cache', true);

        $result = parent::cleanup() && $result;

		return $result;
	}
}