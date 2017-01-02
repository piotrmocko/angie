<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2017 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieControllerUpdatealias extends AController
{
    public function apply()
    {
        $aliases = $this->input->getString('newAliases', '');
        $directories = $this->input->getString('newDirectories', '');

        /** @var AngieModelUpdatealias $model */
        $model = $this->getThisModel();
        $model->updateAliases($aliases, $directories);

        $this->setRedirect('index.php?view=finalise');
    }
}