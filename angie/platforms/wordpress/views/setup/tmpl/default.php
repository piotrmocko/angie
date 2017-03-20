<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2017 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 *
 * Akeeba Next Generation Installer For Joomla!
 */

defined('_AKEEBA') or die();

/** @var $this AView */

$document = $this->container->application->getDocument();

$document->addScript('angie/js/json.js');
$document->addScript('angie/js/ajax.js');
$document->addScript('platform/js/setup.js');
$url = 'index.php';
$document->addScriptDeclaration(<<<ENDSRIPT
var akeebaAjax = null;
$(document).ready(function(){
	akeebaAjax = new akeebaAjaxConnector('$url');
});
ENDSRIPT
);

$this->loadHelper('select');

echo $this->loadAnyTemplate('steps/buttons');
echo $this->loadAnyTemplate('steps/steps', array('helpurl' => 'https://www.akeebabackup.com/documentation/solo/angie-wordpress-setup.html'));
?>
<form name="setupForm" action="index.php" method="post">
	<input type="hidden" name="view" value="setup" />
	<input type="hidden" name="task" value="apply" />

	<div class="row-fluid">
		<!-- Site parameters -->
		<div class="span6">
			<h3><?php echo AText::_('SETUP_HEADER_SITEPARAMS') ?></h3>
			<div class="form-horizontal">
				<div class="control-group">
					<label class="control-label" for="blogname">
						<?php echo AText::_('SETUP_LBL_SITENAME'); ?>
					</label>
					<div class="controls">
						<input type="text" id="blogname" name="blogname" value="<?php echo $this->stateVars->blogname ?>" />
						<span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
							  title="<?php echo AText::_('SETUP_LBL_SITENAME_HELP') ?>"></span>
					</div>
				</div>
				<div class="control-group">
					<label class="control-label" for="blogdescription">
						<?php echo AText::_('SETUP_LBL_TAGLINE'); ?>
					</label>
					<div class="controls">
						<input type="text" id="blogdescription" name="blogdescription" value="<?php echo $this->stateVars->blogdescription ?>" />
						<span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
							  title="<?php echo AText::_('SETUP_LBL_TAGLINE_HELP') ?>"></span>
					</div>
				</div>
				<div class="control-group">
					<label class="control-label" for="homeurl">
						<?php echo AText::_('SETUP_LBL_WPADDRESS'); ?>
					</label>
					<div class="controls">
						<input type="text" id="homeurl" name="homeurl" value="<?php echo $this->stateVars->homeurl ?>" />
						<span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
							  title="<?php echo AText::_('SETUP_LBL_WPADDRESS_HELP') ?>"></span>
					</div>
				</div>
				<div class="control-group">
					<label class="control-label" for="siteurl">
						<?php echo AText::_('SETUP_LBL_SITEADDRESS'); ?>
					</label>
					<div class="controls">
						<input type="text" id="siteurl" name="siteurl" value="<?php echo $this->stateVars->siteurl ?>" />
						<span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
							  title="<?php echo AText::_('SETUP_LBL_SITEADDRESS_HELP') ?>"></span>
					</div>
				</div>

                <div class="control-group">
                    <label class="control-label" for="dbcharset">
                        <?php echo AText::_('SETUP_LBL_CHARSET'); ?>
                    </label>
                    <div class="controls">
                        <input type="text" id="dbcharset" name="dbcharset" value="<?php echo $this->stateVars->dbcharset ?>" />
						<span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
                              title="<?php echo AText::_('SETUP_LBL_CHARSET_HELP') ?>"></span>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="dbcollation">
                        <?php echo AText::_('SETUP_LBL_COLLATION'); ?>
                    </label>
                    <div class="controls">
                        <input type="text" id="dbcollation" name="dbcollation" value="<?php echo $this->stateVars->dbcollation ?>" />
						<span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
                              title="<?php echo AText::_('SETUP_LBL_COLLATION_HELP') ?>"></span>
                    </div>
                </div>
            </div>
		</div>

        <?php if (isset($this->stateVars->superusers)): ?>
            <!-- Super Administrator settings -->
            <div class="span6">
                <h3><?php echo AText::_('SETUP_HEADER_SUPERUSERPARAMS') ?></h3>
                <div class="form-horizontal">
                    <div class="control-group">
                        <label class="control-label" for="superuserid">
                            <?php echo AText::_('SETUP_LABEL_SUPERUSER'); ?>
                        </label>
                        <div class="controls">
                            <?php echo AngieHelperSelect::superusers(); ?>
                            <span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
                                  title="<?php echo AText::_('SETUP_LABEL_SUPERUSER_HELP') ?>"></span>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="superuseremail">
                            <?php echo AText::_('SETUP_LABEL_SUPERUSEREMAIL'); ?>
                        </label>
                        <div class="controls">
                            <input type="text" id="superuseremail" name="superuseremail" value="" />
						<span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
                              title="<?php echo AText::_('SETUP_LABEL_SUPERUSEREMAIL_HELP') ?>"></span>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="superuserpassword">
                            <?php echo AText::_('SETUP_LABEL_SUPERUSERPASSWORD'); ?>
                        </label>
                        <div class="controls">
                            <input type="password" id="superuserpassword" name="superuserpassword" value="" />
						<span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
                              title="<?php echo AText::_('SETUP_LABEL_SUPERUSERPASSWORD_HELP2') ?>"></span>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="superuserpasswordrepeat">
                            <?php echo AText::_('SETUP_LABEL_SUPERUSERPASSWORDREPEAT'); ?>
                        </label>
                        <div class="controls">
                            <input type="password" id="superuserpasswordrepeat" name="superuserpasswordrepeat" value="" />
						<span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
                              title="<?php echo AText::_('SETUP_LABEL_SUPERUSERPASSWORDREPEAT_HELP') ?>"></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
	</div>
</form>

<div id="browseModal" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true" aria-labelledby="browseModalLabel">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="browseModalLabel"><?php echo AText::_('GENERIC_FTP_BROWSER');?></h3>
	</div>
	<div class="modal-body">
		<iframe id="browseFrame" src="index.php?view=ftpbrowser" width="100%" height="300px"></iframe>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">
			<?php echo AText::_('SESSION_BTN_CANCEL') ?>
		</button>
	</div>
</div>

<script type="text/javascript">
<?php if (isset($this->stateVars->superusers)): ?>
setupSuperUsers = <?php echo json_encode($this->stateVars->superusers); ?>;
$(document).ready(function(){
	setupSuperUserChange();
});
<?php endif; ?>

</script>
