<?php
/**
 * create some shortcut-defines for often-needed strings. after this you can include boot.php
 * @package kata_internal
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

/**
 * @ignore
 */
if (php_sapi_name() == 'cli') {
	define('CLI',1);
} else {
	define('CLI',0);
}


/**
 * our error-level constants
 */
define('KATA_DEBUG', 2);
define('KATA_ERROR', 1);
define('KATA_PANIC', 0);

/**
 * some often used constants that should be part of PHP
 */
define('SECOND', 1);
define('MINUTE', 60 * SECOND);
define('HOUR', 60 * MINUTE);
define('DAY', 24 * HOUR);
define('WEEK', 7 * DAY);
define('MONTH', 30 * DAY);
define('YEAR', 365 * DAY);
