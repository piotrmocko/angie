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

$document->addScript('angie/js/json.js');
$document->addScript('angie/js/ajax.js');
$document->addScript('platform/js/setup.js');

$url = 'index.php';
$hashUrl = AUri::base().'platform/newhash.php';

$document->addScriptDeclaration(<<<JS
var akeebaAjax = null;
var hashUrl    = '$hashUrl';
$(document).ready(function(){
	akeebaAjax = new akeebaAjaxConnector('$url');
});
JS
);

$this->loadHelper('select');

echo $this->loadAnyTemplate('steps/buttons');
echo $this->loadAnyTemplate('steps/steps', array('helpurl' => 'https://www.akeebabackup.com/documentation/solo/angie-drupal-setup.html'));

$key = str_replace('.', '_', $this->input->getCmd('substep', 'default'));

?>
<?php
    // The modal window is displayed only when we have a multi site environment and we have to change the settings.php
    // file multiple times
?>
<div class="modal hide fade" id="restoration-dialog">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true" id="restoration-btn-modalclose">&times;</button>
        <h3><?php echo AText::_('SETUP_HEADER_UPDATE') ?></h3>
    </div>
    <div class="modal-body" style="max-height: 500px;">
        <div id="restoration-progress">
            <div class="progress progress-striped active">
                <div class="bar" id="restoration-progress-bar" style="width: 40%;"></div>
            </div>
        </div>
        <div id="restoration-success">
            <div class="alert alert-success">
                <?php echo AText::_('SETUP_HEADER_SUCCESS'); ?>
            </div>
            <p>
                <?php echo AText::_('SETUP_MSG_SUCCESS'); ?>
            </p>
            <button type="button" onclick="setupBtnSuccessClick(); return false;" class="btn btn-success">
                <span class="icon-white icon-check"></span>
                <?php echo AText::_('SETUP_BTN_SUCCESS'); ?>
            </button>
        </div>
        <div id="restoration-error">
            <div class="alert alert-error">
                <?php echo AText::_('SETUP_HEADER_ERROR'); ?>
            </div>
            <div class="well well-small" id="restoration-lbl-error">

            </div>

            <textarea id="restoration-config" style="line-height: normal;width:100%;display:none;height:150px"></textarea>

            <button id="nextStep" style="display:none" type="button" onclick="setupBtnSuccessClick(); return false;" class="btn btn-success">
                <span class="icon-white icon-check"></span>
                <?php echo AText::_('SETUP_BTN_SUCCESS'); ?>
            </button>
        </div>
    </div>
</div>

<form name="setupForm" action="index.php" method="post">
	<input type="hidden" name="view" value="setup" />
	<input type="hidden" name="task" value="apply" />
	<input type="hidden" name="format" value="" />
	<input type="hidden" name="substep" value="<?php echo $key ?>" />

	<div class="row-fluid">
		<!-- Site parameters -->
		<div class="span6">
			<h3><?php echo AText::_('SETUP_HEADER_SITEPARAMS') ?></h3>
			<div class="form-horizontal">
				<div class="control-group">
					<label class="control-label" for="sitename">
						<?php echo AText::_('SETUP_LBL_SITENAME'); ?>
					</label>
					<div class="controls">
						<input type="text" id="sitename" name="<?php echo $key.'_'?>sitename" value="<?php echo $this->stateVars->sitename ?>" />
						<span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
							  title="<?php echo AText::_('SETUP_LBL_SITENAME_HELP') ?>"></span>
					</div>
				</div>
				<div class="control-group">
					<label class="control-label" for="siteemail">
						<?php echo AText::_('SETUP_LBL_SITEEMAIL'); ?>
					</label>
					<div class="controls">
						<input type="text" id="siteemail" name="<?php echo $key.'_'?>siteemail" value="<?php echo $this->stateVars->siteemail ?>" />
						<span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
							  title="<?php echo AText::_('SETUP_LBL_SITEEMAIL_HELP') ?>"></span>
					</div>
				</div>
                <div class="control-group">
                    <label class="control-label" for="livesite">
                        <?php echo AText::_('SETUP_LBL_LIVESITE'); ?>
                    </label>
                    <div class="controls">
                        <input type="text" id="livesite" name="<?php echo $key.'_'?>livesite" value="<?php echo $this->stateVars->livesite ?>" />
						<span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
                              title="<?php echo AText::_('SETUP_LBL_LIVESITE_HELP') ?>"></span>
                    </div>
                </div>
				<div class="control-group">
					<label class="control-label" for="cookiedomain">
						<?php echo AText::_('SETUP_LBL_COOKIEDOMAIN'); ?>
					</label>
					<div class="controls">
						<input type="text" id="cookiedomain" name="<?php echo $key.'_'?>cookiedomain" value="<?php echo $this->stateVars->cookiedomain ?>" />
						<span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
							  title="<?php echo AText::_('SETUP_LBL_COOKIEDOMAIN_HELP') ?>"></span>
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
                            <?php echo AngieHelperSelect::superusers(null, $key.'_superuserid'); ?>
                            <span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
                                  title="<?php echo AText::_('SETUP_LABEL_SUPERUSER_HELP') ?>"></span>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="superuseremail">
                            <?php echo AText::_('SETUP_LABEL_SUPERUSEREMAIL'); ?>
                        </label>
                        <div class="controls">
                            <input type="text" id="superuseremail" name="<?php echo $key.'_'?>superuseremail" value="" />
						<span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
                              title="<?php echo AText::_('SETUP_LABEL_SUPERUSEREMAIL_HELP') ?>"></span>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="superuserpassword">
                            <?php echo AText::_('SETUP_LABEL_SUPERUSERPASSWORD'); ?>
                        </label>
                        <div class="controls">
                            <input type="password" id="superuserpassword" name="<?php echo $key.'_'?>superuserpassword" value="" />
						<span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
                              title="<?php echo AText::_('SETUP_LABEL_SUPERUSERPASSWORD_HELP2') ?>"></span>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="superuserpasswordrepeat">
                            <?php echo AText::_('SETUP_LABEL_SUPERUSERPASSWORDREPEAT'); ?>
                        </label>
                        <div class="controls">
                            <input type="password" id="superuserpasswordrepeat" name="<?php echo $key.'_'?>superuserpasswordrepeat" value="" />
						<span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
                              title="<?php echo AText::_('SETUP_LABEL_SUPERUSERPASSWORDREPEAT_HELP') ?>"></span>
                        </div>
                    </div>

                    <input type="hidden" id="hash" name="<?php echo $key ?>_hash" value="" />
                </div>
            </div>
        <?php endif; ?>
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
					<label class="control-label" for="tmppath">
						<?php echo AText::_('SETUP_LABEL_TMPPATH'); ?>
					</label>
					<div class="controls">
						<input type="text" id="tmppath" name="<?php echo $key.'_'?>tmppath" value="<?php echo $this->stateVars->tmppath ?>" />
						<span class="help-tooltip icon-question-sign" data-toggle="tooltip" data-html="true" data-placement="top"
							  title="<?php echo AText::_('SETUP_LABEL_TMPPATH_HELP') ?>"></span>
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
	setupDefaultTmpDir = '<?php echo addcslashes($this->stateVars->default_tmp, '\\') ?>';
	setupDefaultLogsDir = '<?php echo addcslashes($this->stateVars->default_log, '\\') ?>';
});
<?php endif; ?>

</script>
