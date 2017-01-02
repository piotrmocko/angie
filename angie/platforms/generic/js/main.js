/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2017 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

$(document).ready(function(){
	$('.nav-collapse a').addClass('disabled');
	setTimeout(mainGetPage, 500);
});

function mainGetPage()
{
	request_data = {
		'view':		'main',
		'task':		'main',
		'layout':	'init',
		'format':	'raw'
	};
	akeebaAjax.callRaw(request_data, mainGotPage, mainGotPage);
}

function mainGotPage(html)
{
	$('#wrap > .container').html(html);
	$('.nav-collapse a').removeClass('disabled');
}