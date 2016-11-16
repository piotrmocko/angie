<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieModelGravMain extends AngieModelBaseMain
{
	/**
	 * Try to detect the Pagekit version in use
	 */
	public function detectVersion()
	{
		$ret = '1.0.0';

		$filename = APATH_ROOT . '/system/defines.php';

		if (file_exists($filename))
		{
			include $filename;

			$ret = GRAV_VERSION;
		}

		$this->container->session->set('version', $ret);
		$this->container->session->saveData();
	}

	/**
	 * Get the required settings analysis
	 *
	 * @return  array
	 */
	public function getRequired()
	{
		static $phpOptions = array();

		if (empty($phpOptions))
		{
			$minPHPVersion = '5.5.9';

			$phpOptions[] = array (
				'label'		=> AText::sprintf('MAIN_LBL_REQ_PHP_VERSION', $minPHPVersion),
				'current'	=> version_compare(phpversion(), $minPHPVersion, 'ge'),
				'warning'	=> false,
			);

			$phpOptions[] = array(
				'label'		=> AText::_('MAIN_LBL_REQ_MBSTRING'),
				'current'	=> extension_loaded('mbstring'),
				'warning'	=> false,
			);

			$phpOptions[] = array (
				'label'		=> AText::_('MAIN_LBL_REQ_INIPARSER'),
				'current'	=> $this->getIniParserAvailability(),
				'warning'	=> false,
			);

			$phpOptions[] = array (
				'label'		=> AText::_('MAIN_REC_XML'),
				'current'	=> extension_loaded('xml'),
				'warning'   => false,
			);

			$phpOptions[] = array(
				'label'		=> AText::_('MAIN_LBL_REQ_GD'),
				'current'	=> extension_loaded('gd'),
				'warning'	=> false,
			);

			$phpOptions[] = array(
				'label'		=> AText::_('MAIN_LBL_REQ_OPENSSL'),
				'current'	=> extension_loaded('openssl'),
				'warning'	=> false,
			);

			$phpOptions[] = array(
				'label'		=> AText::_('MAIN_REC_CURL'),
				'current'	=> function_exists('curl_init'),
				'warning'	=> false,
			);

			$phpOptions[] = array(
				'label'		=> AText::_('MAIN_REC_NATIVEZIP'),
				'current'	=> function_exists('zip_open') && function_exists('zip_read'),
				'warning'	=> false,
			);

			$cW = (@ file_exists('../user/config/site.yaml') && @is_writable('../user/config/site.yaml')) || @is_writable('../');
			$phpOptions[] = array (
				'label'		=> AText::_('MAIN_LBL_REQ_CONFIGURATIONPHP'),
				'current'	=> $cW,
				'notice'	=> $cW ? null : AText::_('MAIN_MSG_CONFIGURATIONPHP'),
				'warning'	=> true
			);
		}

		return $phpOptions;
	}

	public function getRecommended()
	{
		static $phpOptions = array();

		if (empty($phpOptions))
		{
			$phpOptions[] = array(
				'label'			=> AText::_('MAIN_REC_DISPERRORS'),
				'current'		=> (bool) ini_get('display_errors'),
				'recommended'	=> false,
			);
		}

		return $phpOptions;
	}
}