/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2017 Nicholas K. Dionysopoulos. All rights reserved.
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
		document.forms.setupForm.submit();
		return false;
	});
});


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
}