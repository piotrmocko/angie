<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2017 Nicholas K. Dionysopoulos. All rights reserved.
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
		/** @var AngieModelDatabase $model */
		$model      = AModel::getAnInstance('Database', 'AngieModel', array());
		$keys       = $model->getDatabaseNames();
		$firstDbKey = array_shift($keys);

		$connectionVars = $model->getDatabaseInfo($firstDbKey);

		// If I have a sqlite database I have to skip database restoration. It's just a simple file to drop
		if ($connectionVars->dbtype == 'sqlite')
		{
			if (isset($steps['database']))
			{
				unset($steps['database']);
			}
		}

		return $steps;
	}
}