<?php
/**
 * several convenience defines and functions
 *
 * Kata - Lightweight MVC Framework <http://www.codeninja.de/>
 * Copyright 2007-2008, mnt@codeninja.de
 *
 * Licensed under The GPL License
 * Redistributions of files must retain the above copyright notice.
 * @package kata
 */




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

/**
 * our error-level constants
 */
define('KATA_DEBUG', 2);
define('KATA_ERROR', 1);
define('KATA_PANIC', 0);

require (ROOT . 'lib' . DS . 'class_registry.php');

/**
 * include the baseclass of whatever a model or controller is derived from.
 * this happens only once per request, so the penalty of using __autoload is small.
 *
 * @param string $cname classname
 */
function kataAutoloader($cname) {
	$cname = strtolower($cname);
	switch ($cname) {
		case 'appmodel' :
			if (file_exists(ROOT . 'models' . DS . 'app_model.php')) {
				require (ROOT . 'models' . DS . 'app_model.php');
			} else {
				require (LIB . 'models' . DS . 'app_model.php');
			}
			break;

		case 'appcontroller' :
			if (file_exists(ROOT . 'controllers' . DS . 'app_controller.php')) {
				require (ROOT . 'controllers' . DS . 'app_controller.php');
			} else {
				require (LIB . 'controllers' . DS . 'app_controller.php');
			}
			break;

		case 'scaffoldcontroller' :
			require (LIB . 'controllers' . DS . 'scaffold_controller.php');
			break;

		case 'cachecontroller':
			require(LIB.'controllers'.DS.'cache_controller.php');
			break;

		case 'memcache' :
			break;

		default :
			if (substr($cname, -9, 9) == 'component') {
				$cname = substr($cname,0,-9);
				if (file_exists(LIB . 'controllers' . DS . 'components' . DS . $cname . '.php')) {
					require LIB . 'controllers' . DS . 'components' . DS . $cname . '.php';
					break;
				}
				require ROOT . 'controllers' . DS . 'components' . DS . $cname . '.php';
				break;
			}
			if (substr($cname, -6, 6) == 'helper') {
				$cname = substr($cname,0,-6);
				if (file_exists(LIB . 'views' . DS . 'helpers' . DS . $cname . '.php')) {
					require LIB . 'views' . DS . 'helpers' . DS . $cname . '.php';
					break;
				}
				require ROOT . 'views' . DS . 'helpers' . DS . $cname . '.php';
				break;
			}
			if (substr($cname, -7, 7) == 'utility') {
				if (file_exists(LIB . 'utilities' . DS . $cname . '.php')) {
					require (LIB . 'utilities' . DS . $cname . '.php');
					break;
				}
				require (ROOT . 'utilities' . DS . $cname . '.php');
				break;
			}
			if (file_exists(LIB . $cname . '.php')) {
				kataUse($cname);
				break;
			}
			break;
	}
}
spl_autoload_register('kataAutoloader');

/**
 * print out type and content of the given variable if DEBUG-define (in config/core.php) > 0
 * @param mixed $var     Variable to debug
 * @param boolean $escape  If set to true variables content will be html-escaped
 */
function debug($var = false, $escape = false) {
	if (DEBUG > 0) {
		$var = var_export($var, true);
		if ($escape) {
			$var = htmlspecialchars($var);
		}
		kataDebugOutput($var);
	}
}

/**
 * internal function to send kata debug info to the browser. just define your own function if you want firebug or something like it
 */
if (!function_exists('kataDebugOutput')) {
	/**
	 * @ignore
	 * @param mixed $var variable to dump
	 * @param bool $isTable if variable is an array we use a table to display each line
	 */
	function kataDebugOutput($var = null, $isTable = false) {
		if (DEBUG < 2) {
			return;
		}
		if ($isTable) {
			echo '<table style="border:1px solid red;color:black;background-color:#e8e8e8;border-collapse:collapse;">';
			foreach ($var as $row) {
				echo '<tr>';
				foreach ($row as $col) {
					echo '<td style="border:1px solid red;padding:2px;">' . $col . '</td>';
				}
				echo '</tr>';
			}
			echo '</table>';
		} else {
			echo '<pre style="border:1px solid red;color:black;background-color:#e8e8e8;padding:3px;">' . $var . '</pre>';
		}
	}
}

/**
 * return the shortend path of the file currently begin executed
 * @return string
 */
function kataGetLineInfo() {
	return;
	$nestLevel = -1;
	$bt = debug_backtrace();
	while ($nestLevel++ < count($bt)) {
		if (empty ($bt[$nestLevel]['file']))
			continue;
		foreach (array (
				LIB,
				ROOT . 'utilities' . DS
			) as $test) {
			if (substr($bt[$nestLevel]['file'], 0, strlen($test)) == $test)
				continue 2;
		}
		break;
	}
	return basename($bt[$nestLevel]['file']) . ':' . $bt[$nestLevel]['line'];
}

/**
 * return stacktrace-like information about the given variable
 * @return string
 */
function kataGetValueInfo($val) {
	if (is_null($val)) {
		return 'null';
	}
	if (is_array($val)) {
		return 'array[' . count($val) . ']';
	}
	if (is_bool($val)) {
		return ($val ? 'true' : 'false');
	}
	if (is_float($val) || is_int($val) || is_long($val) || is_real($val)) {
		return 'num:' . $val;
	}
	if (is_string($val)) {
		return 'string[' . strlen($val) . ']=' . substr($val, 0, 16);
	}
	if (is_resource($val)) {
		return 'resource' . get_resource_type($val);
	}
	if (is_object($val)) {
		return 'object';
	}
	return '?';
}

/**
 * Recursively strips slashes from all values in an array
 * @param mixed $value
 * @return mixed
 */
function stripslashes_deep($value) {
	if (is_array($value)) {
		return array_map('stripslashes_deep', $value);
	} else {
		return stripslashes($value);
	}
}

/**
 * create a directory in TMPPATH and check if its writable
 */
function kataMakeTmpPath($dirname) {
	if (!file_exists(KATATMP.$dirname.DS)) {
		if (!mkdir(KATATMP.$dirname,0770,true)) {
			throw new Exception("kataMakeTmpPath: cant create temporary path $dirname");
		}
	}
	if (!is_writable(KATATMP.$dirname)) {
		throw new Exception("kataMakeTmpPath: ".KATATMP."$dirname is not writeable");
	}
}

/**
 * Recursively urldecodes all values in an array
 * @param mixed $value
 * @return mixed
 */
function urldecode_deep($value) {
	if (is_array($value)) {
		return array_map('urldecode_deep', $value);
	} else {
		return urldecode($value);
	}
}

/** write a string to the log in KATATMP/logs
 *@param string $what string to write to the log
 *@param int $where log-level to log (default: KATA_DEBUG)
 */
function writeLog($what, $where = KATA_DEBUG) {
	if ((DEBUG < 0) && (KATA_DEBUG == $where)) {
		return;
	}

	$logname = 'error';
	if (KATA_DEBUG == $where) {
		$logname = 'debug';
	}
	elseif (KATA_PANIC == $where) {
		$logname = 'panic';
	}


	kataMakeTmpPath('logs');
	$h = fopen(KATATMP. 'logs' . DS . $logname . '.log', 'a');
	if ($h) {
		fwrite($h, date('d.m.Y H:i ') . $what . "\n");
		fclose($h);
	}
}

/**
 * include all neccessary classes and the given model
 * @param string model name without .php - if null it just loads all needed classes
 */
function loadModel($name) {
	if (file_exists(ROOT . 'models' . DS . strtolower($name) . '.php')) {
		require (ROOT . 'models' . DS . strtolower($name) . '.php');
		return;
	}
	throw new Exception('basics: loadModel: cant find Model [' . $name . ']');
}

/**
 * return a handle to the given model. loads and initializes the model if needed.
 * @param string $value model name (without .php)
 * @return object
 */
function getModel($name) {
	if (!class_exists($name)) {
		loadModel($name);
	}
	$o = classRegistry :: getObject($name);
	return $o;
}

/**
 * return class-handle of a utility-class
 *
 * @param string $name name of the utility
 * @return object class-handle
 */
function getUtil($name) {
	$classname = $name . 'Utility';
	if (!class_exists($classname)) {
		require (ROOT . 'utilities' . DS . strtolower($classname) . '.php');
	}
	return classRegistry :: getObject($classname);
}

/**
 * Gets an environment variable from available sources.
 * Used as a backup if $_SERVER/$_ENV are disabled.
 *
 * @param  string $key Environment variable name.
 * @return string Environment variable setting.
 */
function env($key) {
	if ($key == 'HTTPS') {
		if (isset ($_SERVER) && !empty ($_SERVER)) {
			return (isset ($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
		} else {
			return (strpos(env('SCRIPT_URI'), 'https://') === 0);
		}
	}

	if (isset ($_SERVER[$key])) {
		return $_SERVER[$key];
	}
	elseif (isset ($_ENV[$key])) {
		return $_ENV[$key];
	}
	elseif (getenv($key) !== false) {
		return getenv($key);
	}

	if ($key == 'DOCUMENT_ROOT') {
		$offset = 0;
		if (!strpos(env('SCRIPT_NAME'), '.php')) {
			$offset = 4;
		}
		return substr(env('SCRIPT_FILENAME'), 0, strlen(env('SCRIPT_FILENAME')) - (strlen(env('SCRIPT_NAME')) + $offset));
	}
	if ($key == 'PHP_SELF') {
		return r(env('DOCUMENT_ROOT'), '', env('SCRIPT_FILENAME'));
	}
	return null;
}

/**
 * merge any number of arrays
 * @param array first array
 * @param array second array and so on
 * @return array the merged array
 */
function am() {
	$result = array ();
	foreach (func_get_args() as $arg) {
		if (!is_array($arg)) {
			$arg = array (
				$arg
			);
		}
		$result = array_merge($result, $arg);
	}
	return $result;
}

/**
 * load files from the from LIB-directory
 * @param string filename without .php
 */
function kataUse() {
	$args = func_get_args();
	foreach ($args as $arg) {
		require_once (LIB . strtolower($arg) . '.php');
	}
}

/**
 * loads the given files in the VENDORS directory if not already loaded
 * @param string $name Filename without the .php part.
 */
function vendor($name) {
	$args = func_get_args();
	foreach ($args as $arg) {
		if (file_exists(ROOT . 'vendors' . DS . $arg . '.php')) {
			require_once (ROOT . 'vendors' . DS . $arg . '.php');
		}
	}
}

/**
 * Convenience method for htmlspecialchars. you should use this instead of echo to avoid xss-exploits
 * @param string $text
 * @return string
 */
function h($text) {
	if (is_array($text)) {
		return array_map('h', $text);
	}
	return htmlspecialchars($text);
}

/**
 * convenience method to check if given value is set. if so, value is return, otherwise the default
 * @param mixed $arg value to check
 * @param mixed $default value returned if $value is unset
 */
function is(& $arg, $default = null) {
	if (isset ($arg)) {
		return $arg;
	}
	return $default;
}
