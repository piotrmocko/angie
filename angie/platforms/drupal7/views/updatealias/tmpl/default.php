<?php
/**
 * @package angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 *
 * Akeeba Next Generation Installer For Joomla!
 */

defined('_AKEEBA') or die();
/** @var $this AView */

$document = $this->container->application->getDocument();

$document->addScript('platform/js/updatealias.js');

echo $this->loadAnyTemplate('steps/buttons');
echo $this->loadAnyTemplate('steps/steps');
?>
<form name="angieForm" action="index.php" method="post">
	<input type="hidden" name="view" value="updatealias" />
	<input type="hidden" name="task" value="apply" />

    <h2><?php echo AText::_('UPDATEALIAS_TITLE')?></h2>

    <p><?php echo AText::_('UPDATEALIAS_CURRENT')?></p>

    <table class="table table-striped">
        <thead>
            <tr>
                <th><?php echo AText::_('UPDATEALIAS_ALIASES') ?></th>
                <th><?php echo AText::_('UPDATEALIAS_DIRECTORIES') ?></th>
            </tr>
        </thead>
    <?php foreach($this->aliases as $alias => $directory):?>
        <tr>
            <td><?php echo $alias; ?></td>
            <td><?php echo $directory; ?></td>
        </tr>
    <?php endforeach;?>
    </table>

    <p>
        <?php echo AText::_('UPDATEALIAS_NEW_ALIAS_INFO')?>
    </p>

    <div class="span6">
        <h3><?php echo AText::_('UPDATEALIAS_ALIASES')?></h3>
        <textarea name="newAliases" style="width: 100%;height: 150px;"></textarea>
    </div>
    <div class="span5">
        <h3><?php echo AText::_('UPDATEALIAS_DIRECTORIES')?></h3>
        <textarea name="newDirectories" style="width: 100%;height: 150px"></textarea>
    </div>
</form>
