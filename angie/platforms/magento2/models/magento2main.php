<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieModelMagento2Main extends AngieModelBaseMain
{
	/**
	 * Try to detect the Magento version in use
	 */
	public function detectVersion()
	{
        $ret = '1.0.0';

        $filename = APATH_ROOT . '/vendor/magento/framework/AppInterface.php';

        if(file_exists($filename))
        {
            // The version file is deep inside Magento framework, so we can't instantiate the class, but we have
            // to parse the code
            $contents = file_get_contents($filename);

            preg_match("#const\\s*?VERSION\\s*?=\\s*?'(.*?)';#", $contents, $matches);

            if(isset($matches[1]))
            {
                $ret = $matches[1];
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
        $minPHPVersion = '5.5';

        $phpOptions[] = array (
            'label'		=> AText::sprintf('MAIN_LBL_REQ_PHP_VERSION', $minPHPVersion),
            'current'	=> version_compare(phpversion(), $minPHPVersion, 'ge'),
            'warning'	=> false,
        );

        $phpOptions[] = array (
            'label'		=> AText::_('MAIN_LBL_REQ_REGGLOBALS'),
            'current'	=> (ini_get('register_globals') == false),
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

        if (!defined('PDO::ATTR_DRIVER_NAME'))
        {
            $database = false;
        }
        else
        {
            $database = in_array('mysql', PDO::getAvailableDrivers());
        }

        $phpOptions[] = array (
            'label'		=> AText::_('MAIN_LBL_REQ_DATABASE'),
            'current'	=> $database,
            'warning'	=> false,
        );

        $phpOptions[] = array (
            'label'		=> AText::_('MAIN_LBL_REQ_MBSTRING'),
            'current'	=> extension_loaded( 'mbstring' ),
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

        $phpOptions[] = array(
            'label'		=> AText::_('MAIN_REC_SAFEMODE'),
            'current'	=> ((bool)ini_get('safe_mode') == false),
            'warning'	=> false,
        );

        $phpOptions[] = array(
            'label'		=> AText::_('MAIN_REC_CURL'),
            'current'	=> function_exists('curl_init'),
            'warning'	=> false,
        );

        $phpOptions[] = array(
            'label'		=> AText::_('MAIN_LBL_REQ_SIMPLEXML'),
            'current'	=> extension_loaded('simplexml'),
            'warning'	=> false,
        );

        $phpOptions[] = array(
            'label'		=> AText::_('MAIN_LBL_REQ_MCRYPT'),
            'current'	=> extension_loaded('mcrypt'),
            'warning'	=> false,
        );

        $phpOptions[] = array(
            'label'		=> AText::_('MAIN_LBL_REQ_HASH'),
            'current'	=> function_exists('hash'),
            'warning'	=> false,
        );

        $phpOptions[] = array(
            'label'		=> AText::_('MAIN_LBL_REQ_GD'),
            'current'	=> extension_loaded('gd'),
            'warning'	=> false,
        );

        $phpOptions[] = array(
            'label'		=> AText::_('MAIN_LBL_REQ_XSL'),
            'current'	=> extension_loaded('xsl'),
            'warning'	=> false,
        );

        $phpOptions[] = array(
            'label'		=> AText::_('MAIN_LBL_REQ_INTL'),
            'current'	=> extension_loaded('intl'),
            'warning'	=> false,
        );

        $phpOptions[] = array(
            'label'		=> AText::_('MAIN_LBL_REQ_OPENSSL'),
            'current'	=> extension_loaded('openssl'),
            'warning'	=> false,
        );

        $phpOptions[] = array(
            'label'		=> AText::_('MAIN_LBL_REQ_SOAP'),
            'current'	=> extension_loaded('soap'),
            'notice'    => extension_loaded('soap') ? null : AText::_('MAIN_MSG_SOAP'),
            'warning'	=> true,
        );

        $phpOptions[] = array (
            'label'		=> AText::_('MAIN_LBL_REQ_VAR_WRITABLE'),
            'current'	=> @is_writable('../var'),
            'warning'	=> false
        );

        $phpOptions[] = array (
            'label'		=> AText::_('MAIN_LBL_REQ_ETC_WRITABLE'),
            'current'	=> @is_writable('../app/etc'),
            'warning'	=> false
        );

        $phpOptions[] = array (
            'label'		=> AText::_('MAIN_LBL_REQ_PUB_WRITABLE'),
            'current'	=> @is_writable('../pub'),
            'warning'	=> false
        );

		return $phpOptions;
	}

	public function getRecommended()
	{
        $phpOptions[] = array(
            'label'			=> AText::_('MAIN_REC_DISPERRORS'),
            'current'		=> (bool) ini_get('display_errors'),
            'recommended'	=> false,
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

		return $phpOptions;
	}
}