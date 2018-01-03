<?php
/**
 * @package angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
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
