/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2017 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

var akeebaAjaxWP = null;

replacements = {};

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
		'max_exec':		$('#max_exec').val()
	}, replacements.process);
};

replacements.process = function(data)
{
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
	}, replacements.process);
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