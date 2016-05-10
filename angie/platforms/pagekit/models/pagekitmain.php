<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieModelPagekitMain extends AngieModelBaseMain
{
	/**
	 * Try to detect the Pagekit version in use
	 */
	public function detectVersion()
	{
		$ret = '1.0.0';

		$filename = APATH_ROOT . '/app/system/config.php';

		if (file_exists($filename))
		{
			// Pagekit expects that a variable named $path exists, so let's create a dummy one
			$path = 'foobar';
			$config = include $filename;

			$ret = $config['application']['version'];
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
			$minPHPVersion = '5.5.10';

			$phpOptions[] = array (
				'label'		=> AText::sprintf('MAIN_LBL_REQ_PHP_VERSION', $minPHPVersion),
				'current'	=> version_compare(phpversion(), $minPHPVersion, 'ge'),
				'warning'	=> false,
			);

			$phpOptions[] = array (
				'label'		=> AText::_('MAIN_LBL_REQ_ZLIB'),
				'current'	=> extension_loaded('zlib'),
				'warning'	=> false,
			);

			$phpOptions[] = array (
				'label'		=> AText::_('MAIN_LBL_REQ_DATABASE'),
				'current'	=> (function_exists('mysql_connect') || function_exists('mysqli_connect') || function_exists('sqlsrv_connect')),
				'warning'	=> false,
			);

			$phpOptions[] = array(
				'label'		=> AText::_('MAIN_LBL_REQ_SIMPLEXML'),
				'current'	=> extension_loaded('simplexml'),
				'warning'	=> false,
			);

			$phpOptions[] = array(
				'label'		=> AText::_('MAIN_LBL_REQ_MBSTRING'),
				'current'	=> extension_loaded('mbstring'),
				'warning'	=> false,
			);

			$phpOptions[] = array(
				'label'		=> AText::_('MAIN_LBL_REQ_DOM'),
				'current'	=> extension_loaded('dom'),
				'warning'	=> false,
			);

			$phpOptions[] = array (
				'label'		=> AText::_('MAIN_LBL_REQ_INIPARSER'),
				'current'	=> $this->getIniParserAvailability(),
				'warning'	=> false,
			);

			$phpOptions[] = array (
				'label'		=> AText::_('MAIN_LBL_REQ_JSON'),
				'current'	=> function_exists('json_encode') && function_exists('json_decode'),
				'warning'	=> false,
			);

			$cW = (@ file_exists('../app/system/config.php') && @is_writable('../app/system/config.php')) || @is_writable('../');
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

			$phpOptions[] = array(
				'label'			=> AText::_('MAIN_REC_UPLOADS'),
				'current'		=> (bool) ini_get('file_uploads'),
				'recommended'	=> true,
			);

			$phpOptions[] = array (
				'label'		    => AText::_('MAIN_REC_XML'),
				'current'	    => extension_loaded('xml'),
				'recommended'	=> true,
			);

			$phpOptions[] = array(
				'label'		    => AText::_('MAIN_REC_CURL'),
				'current'	    => function_exists('curl_init'),
				'recommended'	=> true,
			);

			$phpOptions[] = array(
				'label'		    => AText::_('MAIN_LBL_REQ_ICONV'),
				'current'	    => extension_loaded('iconv'),
				'recommended'	=> true,
			);

			$phpOptions[] = array(
				'label'			=> AText::_('MAIN_REC_NATIVEZIP'),
				'current'		=> function_exists('zip_open') && function_exists('zip_read'),
				'recommended'	=> true,
			);

		}

		return $phpOptions;
	}
}