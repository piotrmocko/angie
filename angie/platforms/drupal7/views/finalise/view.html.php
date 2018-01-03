<?php
/**
 * @package angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieViewFinalise extends AView
{
	public function onBeforeMain()
	{
        $this->container->application->getDocument()->addScriptDeclaration(<<<ENDSRIPT
var akeebaAjax = null;
$(document).ready(function(){
    akeebaAjax = new akeebaAjaxConnector('index.php');
});
ENDSRIPT
);
		$model = $this->getModel();

		$this->showconfig = $model->getState('showconfig', 0);

		if ($this->showconfig)
		{
			$this->configuration = AModel::getAnInstance('Configuration', 'AngieModel', array(), $this->container)->getFileContents();
		}

		return true;
	}
}
