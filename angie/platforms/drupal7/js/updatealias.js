/**
 * @package angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

/**
 * Initialisation of the page
 */
$(document).ready(function(){
    $('div.navbar div.btn-group a:last').click(function(e){
        document.forms.angieForm.submit();
        return false;
    });
});
