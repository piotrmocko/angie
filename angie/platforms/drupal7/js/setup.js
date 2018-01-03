/**
 * @package angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

var setupSuperUsers = {};
var setupDefaultTmpDir = '';
var setupDefaultLogsDir = '';

/**
 * Initialisation of the page
 */
$(document).ready(function(){
	// Enable tooltips
	$('.help-tooltip').tooltip();

	$('div.navbar div.btn-group a:last').click(function(e){

        // If I have a multisite environment I have to submit the form using AJAX + the modal window
        if($(this).hasClass('btn-multisite'))
        {
            return false;
        }

        document.forms.setupForm.submit();

		return false;
	});
});
function setupRunRestoration(key)
{
    $('input[name="task"]').val('applyjson');
    $('input[name="format"]').val('json');
    $('input[name="substep"]').val(key);

    var data = $('form[name="setupForm"]').serialize();

    // Set up the modal dialog
    $('#restoration-btn-modalclose').hide(0);
    $('#restoration-dialog .modal-body > div').hide(0);
    $('#restoration-progress-bar').css('width', '0%');
    $('#restoration-lbl-restored').text('');
    $('#restoration-lbl-total').text('');
    $('#restoration-progress').show(0);

    // Open the restoration's modal dialog
    $('#restoration-dialog').modal({keyboard: false, backdrop: 'static'});

    // Start the restoration
    setTimeout(function(){akeebaAjax.callJSON(data, setupParseRestoration, setupErrorRestoration);}, 1000);
}

/**
 * Handles a restoration error message
 */
function setupErrorRestoration(error_message, config)
{
    $('#restoration-btn-modalclose').show(0);
    $('#restoration-dialog .modal-body > div').hide(0);
    $('#restoration-lbl-error').html(error_message);

    if(config){
        $('#restoration-lbl-error').height('auto');
        $('#restoration-config').html(config).show();
        $('#nextStep').show();
    }

    $('#restoration-error').show(0);
}

/**
 * Parses the restoration result message, updates the restoration progress bar
 * and steps through the restoration as necessary.
 */
function setupParseRestoration(msg)
{
    if (msg.error != '')
    {
        // An error occurred
        setupErrorRestoration(msg.error, msg.showconfig);

        return;
    }
    else if (msg.done == 1)
    {
        // The restoration is complete
        $('#restoration-progress-bar').css('width', '100%');

        setTimeout(function(){
            $('#restoration-dialog .modal-body > div').hide(0);
            $('#restoration-progress-bar').css('width', '0');
            $('#restoration-success').show(0);
        }, 500);

        return;
    }
}

function setupBtnSuccessClick(e)
{
    window.location = $('.navbar-inner .btn-group a.btn-warning').attr('href');
}

function setupSuperUserChange(e)
{
	var saID = $('#superuserid').val();
	var params = {};

	$.each(setupSuperUsers, function(idx, sa){
		if(sa.id == saID)
		{
			params = sa;
		}
	});

	$('#superuseremail').val('');
	$('#superuserpassword').val('');
	$('#superuserpasswordrepeat').val('');
	$('#superuseremail').val(params.email);
	$('#hash').val('');
}

function setupOverrideDirectories()
{
	$('#tmppath').val(setupDefaultTmpDir);
	$('#logspath').val(setupDefaultLogsDir);
}
