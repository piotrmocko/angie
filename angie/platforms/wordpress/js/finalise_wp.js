/**
 * @package angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

var steps = ['updatehtaccess'];
var akeebaAjaxWP = null;
var totalSteps = steps.length;

function runStep(curStep)
{
	$('#step' + curStep).addClass('label-info');

	akeebaAjaxWP.callJSON({
		'view':			'finalise',
		'task':			'ajax',
		'method':		steps[curStep - 1],
		'format':		'json'
	}, function(){
		$('#step' + curStep).removeClass('label-info').addClass('label-success');

		if (curStep >= totalSteps)
		{
			$('#finalisationSteps').hide('slow');
			$('#finalisationInterface').show();
		}
		else
		{
			setTimeout(function(){runStep(curStep + 1)}, 100);
		}
	});
}

$(document).ready(function(){
	akeebaAjaxWP = new akeebaAjaxConnector('index.php');
	setTimeout(function(){runStep(0)}, 100);
});
