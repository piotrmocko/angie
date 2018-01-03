<?php
/**
 * @package angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieModelUpdatealias extends AModel
{
    public function readAliases()
    {
        $sites = APATH_SITE.'/sites/sites.php';

        if(!file_exists($sites))
        {
            return array();
        }

        $contents = file_get_contents($sites);

        $tokenizer = new AUtilsPhptokenizer($contents);

        $skip   = 0;
        $error  = false;
        $tokens = array();

        // Database info
        while(!$error)
        {
            try
            {
                // Let's try to extract all the occurrences until we get an error. Since it's just a PHP array,
                // you could write it in a million of different ways
                $info = $tokenizer->searchToken('T_VARIABLE', '$sites', $skip);

                $skip     = $info['endLine'] + 1;
                $tokens[] = $info['data'];
            }
            catch(RuntimeException $e)
            {
                $error = true;
            }
        }

        if(!$tokens)
        {
            return array();
        }

        // Let's use Configuration model function to extract the data we need
        /** @var AngieModelDrupal7Configuration $configModel */
        $configModel = AModel::getAnInstance('Configuration', 'AngieModel', array(), $this->container);

        $aliases = $configModel->extractVariables($tokens, 'sites');

        return $aliases;
    }

    public function updateAliases($aliases, $directories)
    {
        // Initialise to empty, so we can blank them out if we don't want them
        $sites = '';

        if($aliases && $directories)
        {
            $aliases     = str_replace("\r", '', $aliases);
            $directories = str_replace("\r", '', $directories);

            $aliases     = explode("\n", $aliases);
            $directories = explode("\n", $directories);

            // Aliases and directories don't match? Stop here
            if(count($aliases) != count($directories))
            {
                return;
            }

            $sites = "\$sites = array(\n";

            for($i = 0; $i < count($aliases); $i++)
            {
                $sites .= "    '".$aliases[$i]."' => '".$directories[$i]."',\n";
            }

            $sites .= ");\n";
        }

        $replace_sites[] = $sites;
        $contents = file_get_contents(APATH_SITE.'/sites/sites.php');

        $out       = $contents;
        $tokenizer = new AUtilsPhptokenizer($contents);
        $error     = false;
        $skip      = 0;

        while(!$error)
        {
            try
            {
                // First time I really want to replace data, in the next loops I simply want to wipe out everything
                if($replace_sites)
                {
                    $replace = array_shift($replace_sites);
                }
                else
                {
                    $replace = '';
                }

                $out  = $tokenizer->replaceToken('T_VARIABLE', '$sites', $skip, $replace);

                $tokenizer->setCode($out);

                $info = $tokenizer->searchToken('T_VARIABLE', '$sites', $skip);
                $skip = $info['endLine'] + 1;
            }
            catch(RuntimeException $e)
            {
                $error = true;
            }
        }

        file_put_contents(APATH_SITE.'/sites/sites.php', $out);
    }
}
