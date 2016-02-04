<?php
/**
 * @package angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 *
 */

// Drupal has a very complicated way to create the password hash: at the moment it perform 2^15 loops
// so we can't possible rewrite the whole logic. The best way is to create a standalone script file
// where we bootstrap Drupal and ask him to create a new password

define('DRUPAL_ROOT', realpath(__DIR__.'/../../'));
require_once DRUPAL_ROOT.'/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

require_once DRUPAL_ROOT . '/includes/password.inc';

if (isset($_GET['pass']) && !empty($_GET['pass']))
{
    echo user_hash_password($_GET['pass']);
}

drupal_exit();