/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2017 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

var akeebaAjaxWP = null;

replacements = {
    resumeTimer:        null,
    resume:
        {
            enabled:      true,
            timeout:      10,
            maxRetries:   3,
            retry:        0,
            showWarnings: 0
        }
};

replacements.start = function()
{
	$('#replacementsGUI').hide('fast');
	$('#replacementsProgress').show('fast');

	akeebaAjaxWP.callJSON({
		'view':			'replacedata',
		'task':			'ajax',
		'method':		'initEngine',
		'format':		'json',
		'replaceFrom':	$('#replaceFrom').val(),
		'replaceTo':	$('#replaceTo').val(),
		'extraTables':	$('#extraTables').val(),
		'batchSize':	$('#batchSize').val(),
		'min_exec':		$('#min_exec').val(),
		'max_exec':		$('#max_exec').val(),
		'runtime_bias':	$('#runtime_bias').val()
	},
        replacements.process,
        replacements.onError
    );
};

replacements.process = function(data)
{
    // Do we have errors?
    var error_message = data.error;

    if (error_message != '')
    {
        try
        {
            console.error('Got an error message');
            console.log(error_message);
        }
        catch (e)
        {
        }

        // Uh-oh! An error has occurred.
        replacements.onError(error_message);

        return;
    }

	$('#blinkenlights').append($('#blinkenlights span:first'));
	$('#replacementsProgressText').text(data.msg);

	if (!data.more)
	{
		window.location = $('#btnNext').attr('href');

		return;
	}

	setTimeout(function(){replacements.step();}, 100);
};

replacements.step = function()
{
	akeebaAjaxWP.callJSON({
		'view':			'replacedata',
		'task':			'ajax',
		'method':		'stepEngine',
		'format':		'json'
	},
        replacements.process,
        replacements.onError
    );
};

/**
 * Resume a backup attempt after an AJAX error has occurred.
 */
replacements.resumeBackup = function ()
{
    // Make sure the timer is stopped
    replacements.resetRetryTimeoutBar();

    // Hide error and retry panels
    document.getElementById('error-panel').style.display = 'none';
    document.getElementById('retry-panel').style.display = 'none';

    // Show progress
    document.getElementById('replacementsProgress').style.display = 'block';

    // Restart the replacements
    setTimeout(function(){replacements.step();}, 100);
};

/**
 * Resets the last response timer bar
 */
replacements.resetRetryTimeoutBar = function ()
{
    clearInterval(replacements.resumeTimer);

    document.getElementById('akeeba-retry-timeout').textContent = replacements.resume.timeout.toFixed(0);
};

/**
 * Starts the timer for the last response timer
 */
replacements.startRetryTimeoutBar = function ()
{
    var remainingSeconds = replacements.resume.timeout;

    replacements.resumeTimer = setInterval(function ()
    {
        remainingSeconds--;
        document.getElementById('akeeba-retry-timeout').textContent = remainingSeconds.toFixed(0);

        if (remainingSeconds == 0)
        {
            clearInterval(replacements.resumeTimer);
            replacements.resumeBackup();
        }
    }, 1000);
};

/**
 * Cancel the automatic resumption of a backup attempt after an AJAX error has occurred
 */
replacements.cancelResume = function ()
{
    // Make sure the timer is stopped
    replacements.resetRetryTimeoutBar();

    // Kill the backup
    var errorMessage = document.getElementById('backup-error-message-retry').innerHTML;
    replacements.endWithError(errorMessage);
};

replacements.onError = function (message)
{
    // If we are past the max retries, die.
    if (replacements.resume.retry >= replacements.resume.maxRetries)
    {
        replacements.endWithError(message);

        return;
    }

    // Make sure the timer is stopped
    replacements.resume.retry++;
    replacements.resetRetryTimeoutBar();

    // Hide progress
    document.getElementById('replacementsProgress').style.display  = 'none';
    document.getElementById('error-panel').style.display           = 'none';

    // Setup and show the retry pane
    document.getElementById('backup-error-message-retry').textContent = message;
    document.getElementById('retry-panel').style.display              = 'block';

    // Start the countdown
    replacements.startRetryTimeoutBar();
};

/**
 * Terminate the backup with an error
 *
 * @param   message  The error message received
 */
replacements.endWithError = function (message)
{
    // Hide progress
    document.getElementById('replacementsProgress').style.display  = 'none';
    document.getElementById('retry-panel').style.display           = 'none';

    // Setup and show error pane
    document.getElementById('backup-error-message').textContent = message;
    document.getElementById('error-panel').style.display        = 'block';
};

$(document).ready(function(){
	akeebaAjaxWP = new akeebaAjaxConnector('index.php');
	// Hijack the Next button
	$('#btnNext').click(function (e){
		setTimeout(function(){replacements.start();}, 100);

		return false;
	});

	$('#showAdvanced').click(function(){
		$(this).hide();
		$('#replaceThrottle').show();
	});
});