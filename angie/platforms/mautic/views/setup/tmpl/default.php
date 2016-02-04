<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 *
 * Akeeba Next Generation Installer For Joomla!
 */

defined('_AKEEBA') or die();

ADocument::getInstance()->addScript('angie/js/json.js');
ADocument::getInstance()->addScript('angie/js/ajax.js');
ADocument::getInstance()->addScript('platform/js/setup.js');
$url = 'index.php';
ADocument::getInstance()->addScriptDeclaration(<<<ENDSRIPT
var akeebaAjax = null;
$(document).ready(function(){
	akeebaAjax = new akeebaAjaxConnector('$url');
});
ENDSRIPT
);

$this->loadHelper('select');

echo $this->loadAnyTemplate('steps/buttons');
echo $this->loadAnyTemplate('steps/steps', array('helpurl' => 'https://www.akeebabackup.com/documentation/solo/angie-joomla-setup.html'));
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
					<label class="control-label" for="siteemail">
						<?php echo AText::_('SETUP_LBL_SITEEMAIL'); ?>
					</label>
					<div class="controls">
						<input type="text" id="siteemail" name="siteemail" value="<?php echo $this->stateVars->siteemail ?>" />
						<span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
							  title="<?php echo AText::_('SETUP_LBL_SITEEMAIL_HELP') ?>"></span>
					</div>
				</div>
				<div class="control-group">
					<label class="control-label" for="emailsender">
						<?php echo AText::_('SETUP_LBL_EMAILSENDER'); ?>
					</label>
					<div class="controls">
						<input type="text" id="emailsender" name="emailsender" value="<?php echo $this->stateVars->emailsender ?>" />
						<span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
							  title="<?php echo AText::_('SETUP_LBL_EMAILSENDER_HELP') ?>"></span>
					</div>
				</div>
				<div class="control-group">
					<label class="control-label" for="livesite">
						<?php echo AText::_('SETUP_LBL_LIVESITE'); ?>
					</label>
					<div class="controls">
						<input type="text" id="livesite" name="livesite" value="<?php echo $this->stateVars->livesite ?>" />
						<span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
							  title="<?php echo AText::_('SETUP_LBL_LIVESITE_HELP') ?>"></span>
						<?php if (substr(PHP_OS, 0, 3) == 'WIN'): ?>
						<span class="help-block alert alert-warning">
							<span class="icon icon-warning-sign"></span>
							<?php echo AText::_('SETUP_LBL_LIVESITE_WINDOWS_WARNING') ?>
						</span>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
        <!-- Super Administrator settings -->
        <div class="span6">
        <?php if (isset($this->stateVars->superusers)): ?>
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
                          title="<?php echo AText::_('SETUP_LABEL_SUPERUSERPASSWORD2_HELP') ?>"></span>
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
        <?php endif; ?>
        </div>
	</div>
	<div class="row-fluid">
		<!-- Fine-tuning -->
		<div class="span6">
			<h3><?php echo AText::_('SETUP_HEADER_FINETUNING') ?></h3>
			<div class="form-horizontal">
				<div class="control-group">
					<label class="control-label" for="siteroot">
						<?php echo AText::_('SETUP_LABEL_SITEROOT'); ?>
					</label>
					<div class="controls">
						<input type="text" disabled="disabled" id="siteroot" value="<?php echo $this->stateVars->site_root_dir ?>" />
						<span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
							  title="<?php echo AText::_('SETUP_LABEL_SITEROOT_HELP') ?>"></span>
					</div>
				</div>
				<div class="control-group">
					<label class="control-label" for="logspath">
						<?php echo AText::_('SETUP_LABEL_LOGSPATH'); ?>
					</label>
					<div class="controls">
						<input type="text" id="logspath" name="logspath" value="<?php echo $this->stateVars->logspath ?>" />
						<span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
							  title="<?php echo AText::_('SETUP_LABEL_LOGSPATH_HELP') ?>"></span>
					</div>
				</div>
			</div>
		</div>
	</div>
</form>

<script type="text/javascript">
<?php if (isset($this->stateVars->superusers)): ?>
setupSuperUsers = <?php echo json_encode($this->stateVars->superusers); ?>;
$(document).ready(function(){
	setupSuperUserChange();
	setupDefaultLogsDir = '<?php echo addcslashes($this->stateVars->default_log, '\\') ?>';
});
<?php endif; ?>

</script>
