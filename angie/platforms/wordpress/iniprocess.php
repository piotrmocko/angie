<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2017 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 *
 */

defined('_AKEEBA') or die();

class IniProcess
{
    /**
     * Language file processing callback. Converts Joomla messages into Wordpress ones
     *
     * @param   string  $filename  The full path to the file being loaded
     * @param   array   $strings   The key/value array of the translations
     *
     * @return  boolean|array  False to prevent loading the file, or array of processed language string, or true to
     *                         ignore this processing callback.
     */
    public static function processLanguageIniFile($filename, $strings)
    {
        foreach ($strings as $k => $v)
        {
            $v = str_replace('Joomla!', 'WordPress', $v);
            $v = str_replace('Joomla', 'WordpPess', $v);
            $v = str_replace('configuration.php', 'wp-config.php', $v);

            $strings[$k] = $v;
        }

        return $strings;
    }
}