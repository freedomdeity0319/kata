<?php
/**
 * includes everything needed to call the dispatcher and start the whole mvc machinery
 * @package kata
 */




/**
 * include all neccessary files to start up the framework
 */
require_once ROOT."config".DS."core.php";

/**
 * in case we dont use ignorant installer software: simply use kata's builtin tmp-dir
 */
if (!defined('KATATMP')) {
	define('KATATMP',ROOT.'tmp'.DS);
}
if (!defined('CACHE_IDENTIFIER')) {
   define('CACHE_IDENTIFIER','kataDef');
}

/**
 * needed for the updater
 */
define('KATAVERSION','1.1');

/**
 * set default encodings to utf-8 (you don't want to use anything less, anyway)
 */
mb_internal_encoding( 'UTF-8' );
mb_regex_encoding('UTF-8');
$timezone = date_default_timezone_get();
if (empty($timezone)) {
	date_default_timezone_set('Europe/Berlin');
}

/**
 * do we have to turn on error messages and asserts?
 */
if (DEBUG>0) {
	error_reporting(E_ALL);
	assert_options(ASSERT_ACTIVE,true);
	assert_options(ASSERT_WARNING,1);
	assert_options(ASSERT_QUIET_EVAL,0);
	ini_set('display_errors',1);
}

require_once LIB."basics.php";
require_once LIB."dispatcher.php";

