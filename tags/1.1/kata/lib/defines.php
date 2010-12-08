<?php
/**
 * create some shortcut-defines for often-needed strings. after this you can include boot.php
 * @package kata
 */





if (!defined('DS')) {
/**
 * shortcut for / or \ (depending on OS)
 */
	define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('ROOT')) {
	/**
	 * absolute filesystem path to the root directory of this framework
	 */
	define('ROOT',dirname(dirname(__FILE__)).DS);
}
if (!defined('WWW_ROOT')) {
	/**
	 * absolute filesystem path to the webroot-directory of this framework
	 */
	define('WWW_ROOT', ROOT.'webroot'. DS);
}
if (!defined('LIB')) {
	/**
	 * absolute filesystem path to the lib-directory of this framework
	 */
	define('LIB',ROOT.'lib'.DS);
}
