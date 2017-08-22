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
        },
	editor: {},
	strings: {}
};

replacements.start = function()
{
	$('#replacementsGUI').hide('fast');
	$('#replacementsProgress').show('fast');

	var request = {
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
	};

	akeebaAjaxWP.callJSON(request,
        replacements.process,
        replacements.onError
    );
};

replacements.process = function(data)
{
    // Do we have errors?
    var error_message = data.error;

    if (error_message !== undefined && error_message != '')
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
 * Resume the data replacement step after an AJAX error has occurred.
 */
replacements.resumeReplacement = function ()
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
            replacements.resumeReplacement();
        }
    }, 1000);
};

/**
 * Cancel the automatic resumption of the replacement step after an AJAX error has occurred
 */
replacements.cancelResume = function ()
{
    // Make sure the timer is stopped
    replacements.resetRetryTimeoutBar();

    // Kill the replacement
    var errorMessage = document.getElementById('replacement-error-message-retry').innerHTML;
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
    document.getElementById('replacement-error-message-retry').textContent = message;
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
    document.getElementById('replacement-error-message').textContent = message;
    document.getElementById('error-panel').style.display        = 'block';
};

replacements.editor.render = function(selector, data)
{
	// Get the row container from the selector
	var elContainer = $(selector);

	// Store the key-value information as a data property
	elContainer.data(elContainer, 'keyValueData', data);

	// Render one GUI row per data row
	for (var valFrom in data)
	{
		// Skip if the key is a property from the object's prototype
		if (!data.hasOwnProperty(valFrom)) continue;

		var valTo = data[valFrom];

		replacements.editor.renderRow(elContainer, valFrom, valTo);
	}

	// Add the last, empty row
	replacements.editor.renderRow(elContainer, "", "");
};

replacements.editor.renderRow = function(elContainer, valFrom, valTo)
{
	var elRow = $("<div />").addClass("keyValueLine row-fluid");

	var elFromInput = $("<input />")
		.addClass("input-large input-100 keyValueFrom")
		.attr("type", "text")
		.attr("title", replacements.strings["lblKey"])
		.attr("placeholder", replacements.strings["lblKey"])
		.val(valFrom);

	var elToInput = $("<input />")
	.addClass("input-large input-100 keyValueTo")
		.attr("type", "text")
		.attr("title", replacements.strings["lblValue"])
		.attr("placeholder", replacements.strings["lblValue"])
		.val(valTo);

	var elDeleteIcon = $("<span />")
		.addClass("icon icon-white icon-trash");

	var elDeleteButton = $("<span />")
		.addClass("btn btn-danger keyValueButtonDelete")
		.addClass("title", replacements.strings["lblDelete"])
	    .append(elDeleteIcon);

	var elUpIcon = $("<span />")
		.addClass("icon icon-arrow-up");

	var elUpButton = $("<span />")
		.addClass("btn btn-small keyValueButtonUp")
	    .append(elUpIcon);

	var elDownIcon = $("<span />")
		.addClass("icon icon-arrow-down");

	var elDownButton = $("<span />")
		.addClass("btn btn-small keyValueButtonDown")
	    .append(elDownIcon);

	var elFromWrapper = $("<div />").addClass("span5 keyValueFromWrapper").append(elFromInput);
	var elToWrapper = $("<div />").addClass("span5 keyValueToWrapper").append(elToInput);
	var elButtonsWrapper = $("<div />").addClass("span2 keyValueButtonsWrapper")
		.append(elDeleteButton)
		.append(elUpButton)
		.append(elDownButton);

	elFromInput.blur(function(e) {
		replacements.editor.reflow(elContainer);
	});

	elToInput.blur(function(e) {
		replacements.editor.reflow(elContainer);
	});

	elDeleteButton.click(function(e) {
		elFromInput.val("");
		elToInput.val("");
		replacements.editor.reflow(elContainer);
	});

	elUpButton.click(function(e) {
		var elPrev = elRow.prev();

		if (!elPrev.length)
		{
			return;
		}

		var elPrevFrom = elPrev.find('.keyValueFrom');
		var elPrevTo = elPrev.find('.keyValueTo');

		var prevFrom = elPrevFrom.val();
		var prevTo = elPrevTo.val();

		elPrevFrom.val(elFromInput.val());
		elPrevTo.val(elToInput.val());
		elFromInput.val(prevFrom);
		elToInput.val(prevTo);

		replacements.editor.reflow(elContainer);
	});

	elDownButton.click(function(e) {
		var elNext = elRow.next();

		if (!elNext.length)
		{
			return;
		}

		var elNextFrom = elNext.find('.keyValueFrom');
		var elNextTo = elNext.find('.keyValueTo');

		var nextFrom = elNextFrom.val();
		var nextTo = elNextTo.val();

		elNextFrom.val(elFromInput.val());
		elNextTo.val(elToInput.val());
		elFromInput.val(nextFrom);
		elToInput.val(nextTo);

		replacements.editor.reflow(elContainer);
	});

	elRow.append(elFromWrapper, elToWrapper, elButtonsWrapper);
	elContainer.append(elRow);
};

replacements.editor.reflow = function(elContainer)
{
	var data = {};
	var strFrom = "";
	var strTo = "";
	var elRows = elContainer.children();
	var hasEmptyRow = false;

	// Convert rows to a data object
	$.each(elRows, function (idx, elRow) {
		var $elRow = $(elRow);
		var valFrom = $elRow.find('.keyValueFrom').val();
		var valTo = $elRow.find('.keyValueTo').val();

		// If the From value is empty I may have to delete this row
		if (valFrom === '')
		{
			if (idx < elRows.length)
			{
				// This is an empty From in a row other than the last. Remove it.
				$elRow.remove();
			}
			else
			{
				// This is the last empty row. Do not remove and set the flag of having a last empty row.
				hasEmptyRow = true;
			}

			return;
		}

		data[valFrom] = valTo;
		strFrom += "\n" + valFrom;
		strTo += "\n" + valTo;
	});

	// If I don't have a last empty row, create one
	if (!hasEmptyRow)
	{
		replacements.editor.renderRow(elContainer, "", "");
	}

	// Store the key-value information as a data property
	elContainer.data(elContainer, 'keyValueData', data);

	// Transfer the data to the textboxes
	$("#replaceFrom").val(strFrom.replace(/^\s+/g, ""));
	$("#replaceTo").val(strTo.replace(/^\s+/g, ""));
};

/**
 * Displays the Javascript powered key-value editor
 */
replacements.showEditor = function ()
{
	var from = $('#replaceFrom').val().split("\n");
	var to = $('#replaceTo').val().split("\n");
	var extractedValues = {};

	for (var i = 0; i < Math.min(from.length, to.length); i++)
	{
		extractedValues[from[i]] = to[i];
	}

	$("#textBoxEditor").hide();
	$("#keyValueEditor").show();
	replacements.editor.render('#keyValueContainer', extractedValues);
};

$(document).ready(function(){
	akeebaAjaxWP = new akeebaAjaxConnector('index.php');
	// Hijack the Next button
	$('#btnNext').click(function (e) {
		setTimeout(function(){replacements.start();}, 100);

		return false;
	});

	$('#showAdvanced').click(function() {
		$(this).hide();
		$('#replaceThrottle').show();
	});
});
