/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
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