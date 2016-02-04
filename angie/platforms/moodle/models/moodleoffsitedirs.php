<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieModelMoodleOffsitedirs extends AngieModelBaseOffsitedirs
{
    /**
     * Override the parent class since I have to store some extra info
     *
     * @param $key
     * @throws Exception
     */
    public function moveDir($key)
    {
        $dirs = $this->getDirs(true, true);
        $dir  = $dirs[$key];
        $info = $this->input->get('info', array(), 'array');

        $virtual = APATH_ROOT.'/'.$dir['virtual'];
        $target  = str_replace(array('[SITEROOT]', '[ROOTPARENT]'), array(APATH_ROOT, realpath(APATH_ROOT.'/..')), $info['target']);

        // Are we trying to restore the moodledata directory? If so let's save the target, so I can update the config file
        if($key == 'moodledata')
        {
            $this->container->session->set('directories.moodledata', $target);
        }

        if(!file_exists($virtual))
        {
            throw new Exception(AText::_('OFFSITEDIRS_VIRTUAL_DIR_NOT_FOUND'), 0);
        }

        if(!$this->recurse_copy($virtual, $target))
        {
            throw new Exception(AText::_('OFFSITEDIRS_VIRTUAL_COPY_ERROR'), 0);
        }
    }
}