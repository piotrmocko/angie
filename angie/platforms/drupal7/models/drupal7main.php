<?php
/**
 * @package angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieModelDrupal7Main extends AngieModelBaseMain
{
	/**
	 * Try to detect the Drupal version in use
	 */
	public function detectVersion()
	{
		$ret = '0.0.0';

		$filename = APATH_ROOT . '/includes/bootstrap.inc';

		if (file_exists($filename))
		{
            // In this file there are a lot of functions and defines, so we can't blindly import it
            $lines = file($filename,  FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

            foreach($lines as $line)
            {
                if(stripos($line, "define('VERSION'") === false)
                {
                    continue;
                }

                preg_match('#define\(\'VERSION\',\s\'(.*?)\'\)#', $line, $matches);

                if(isset($matches[1]))
                {
                    $ret = trim($matches[1]);
                }

                break;
            }
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

            $minPHPVersion = '5.3.4';

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
				'label'		=> AText::_('MAIN_LBL_REQ_XML'),
				'current'	=> extension_loaded('xml'),
				'warning'	=> false,
			);

			$phpOptions[] = array (
				'label'		=> AText::_('MAIN_LBL_REQ_DATABASE'),
				'current'	=> (function_exists('mysql_connect') || function_exists('mysqli_connect') || extension_loaded('pdo_mysql')),
				'warning'	=> false,
			);

			if (extension_loaded( 'mbstring' ))
			{
				$option = array (
					'label'		=> AText::_( 'MAIN_REQ_MBLANGISDEFAULT' ),
					'current'	=> (strtolower(ini_get('mbstring.language')) == 'neutral'),
					'warning'	=> false,
				);
				$option['notice'] = $option['current'] ? null : AText::_('MAIN_MSG_NOTICEMBLANGNOTDEFAULT');
				$phpOptions[] = $option;

				$option = array (
					'label'		=> AText::_('MAIN_REQ_MBSTRINGOVERLOAD'),
					'current'	=> (ini_get('mbstring.func_overload') == 0),
					'warning'	=> false,
				);
				$option['notice'] = $option['current'] ? null : AText::_('MAIN_MSG_NOTICEMBSTRINGOVERLOAD');
				$phpOptions[] = $option;
			}

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

            $phpOptions[] = array (
                'label'		=> AText::_('MAIN_LBL_GD_LIBRARY'),
                'current'	=> extension_loaded('gd') && function_exists('gd_info'),
                'warning'	=> false,
            );

			$cW = (@ file_exists('../sites/default/settings.php') && @is_writable('../sites/default/settings.php')) || @is_writable('../sites/default');
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
				'label'			=> AText::_('MAIN_REC_SAFEMODE'),
				'current'		=> (bool) ini_get('safe_mode'),
				'recommended'	=> false,
			);

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

			$phpOptions[] = array(
				'label'			=> AText::_('MAIN_REC_MCR'),
				'current'		=> (bool) ini_get('magic_quotes_runtime'),
				'recommended'	=> false,
			);

			$phpOptions[] = array(
				'label'			=> AText::_('MAIN_REC_OUTBUF'),
				'current'		=> (bool) ini_get('output_buffering'),
				'recommended'	=> false,
			);

			$phpOptions[] = array(
				'label'			=> AText::_('MAIN_REC_SESSIONAUTO'),
				'current'		=> (bool) ini_get('session.auto_start'),
				'recommended'	=> false,
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
