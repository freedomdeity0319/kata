<?php

/**
 * several functions needed by kata
 *
 * Kata - Lightweight MVC Framework <http://www.codeninja.de/>
 * Copyright 2007-2009 mnt@codeninja.de, gameforge ag
 *
 * Licensed under The GPL License
 * Redistributions of files must retain the above copyright notice.
 * @package kata_internal
 */


/**
 * include files depending on name, if class is needed 
 *
 * @param string $cname classname
 */
function kataAutoloader($classname) {
	$cname= strtolower($classname);
	switch ($cname) {
		case 'appmodel' :
			if (file_exists(ROOT.'models'.DS.'app_model.php')) {
				require ROOT.'models'.DS.'app_model.php';
			} else {
				require LIB.'models'.DS.'app_model.php';
			}
			break;

		case 'appcontroller' :
			if (file_exists(ROOT.'controllers'.DS.'app_controller.php')) {
				require ROOT.'controllers'.DS.'app_controller.php';
			} else {
				require LIB.'controllers'.DS.'app_controller.php';
			}
			break;

		case 'scaffoldcontroller' :
			require LIB.'controllers'.DS.'scaffold_controller.php';
			break;

			/*** GF_SPECIFIC ***/
		case substr($classname, 0, 3) == 'GF_' or substr($classname, 0, 5) == 'Zend_' or substr($classname, 0, 6) == 'ZendX_' :
			require str_replace('_', '/', $classname).'.php';
			break;
			/*** /GF_SPECIFIC ***/
			
		case substr($cname, -9, 9) == 'component' :
			$cname= substr($cname, 0, -9);
			if (file_exists(LIB.'controllers'.DS.'components'.DS.$cname.'.php')) {
				require LIB.'controllers'.DS.'components'.DS.$cname.'.php';
				break;
			}
			require ROOT.'controllers'.DS.'components'.DS.$cname.'.php';
			break;

		case substr($cname, -6, 6) == 'helper' :
			$cname= substr($cname, 0, -6);
			if (file_exists(LIB.'views'.DS.'helpers'.DS.$cname.'.php')) {
				require LIB.'views'.DS.'helpers'.DS.$cname.'.php';
				break;
			}
			require ROOT.'views'.DS.'helpers'.DS.$cname.'.php';
			break;

		case substr($cname, -7, 7) == 'utility' :
			if (file_exists(LIB.'utilities'.DS.$cname.'.php')) {
				require LIB.'utilities'.DS.$cname.'.php';
				break;
			}
			require ROOT.'utilities'.DS.$cname.'.php';
			break;

		case file_exists(LIB.$cname.'.php') :
			kataUse($cname);
			break;

		case 'memcache' :
			break;

	}
}
spl_autoload_register('kataAutoloader');


/**
 * internal function to send kata debug info to the browser. just define your own function if you want firebug or something like it
 */
if (!function_exists('kataDebugOutput')) {
	/**
	 * @ignore
	 * @param mixed $var variable to dump
	 * @param bool $isTable if variable is an array we use a table to display each line
	 */
	function kataDebugOutput($var= null, $isTable= false) {
		if (DEBUG < 2) {
			return;
		}
		if ($isTable) {
			echo '<table style="text-align:left;direction:ltr;border:1px solid red;color:black;background-color:#e8e8e8;border-collapse:collapse;text-align:left;direction:ltr;">';
			foreach ($var as $row) {
				echo '<tr>';
				foreach ($row as $col) {
					echo '<td style="border:1px solid red;padding:2px;">'.$col.'</td>';
				}
				echo '</tr>';
			}
			echo '</table>';
		} else {
			echo '<pre style="text-align:left;direction:ltr;border:1px solid red;color:black;background-color:#e8e8e8;padding:3px;text-align:left;direction:ltr;">'.$var.'</pre>';
		}
	}
}


/**
 * return the shortend path of the file currently begin executed
 * @return string
 */
function kataGetLineInfo() {
	return;
	$nestLevel= -1;
	$bt= debug_backtrace();
	while ($nestLevel++ < count($bt)) {
		if (empty ($bt[$nestLevel]['file']))
			continue;
		foreach (array (
				LIB,
				ROOT.'utilities'.DS
			) as $test) {
			if (substr($bt[$nestLevel]['file'], 0, strlen($test)) == $test)
				continue 2;
		}
		break;
	}
	return basename($bt[$nestLevel]['file']).':'.$bt[$nestLevel]['line'];
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
		return 'array['.count($val).']';
	}
	if (is_bool($val)) {
		return ($val ? 'true' : 'false');
	}
	if (is_float($val) || is_int($val) || is_long($val) || is_real($val)) {
		return 'num:'.$val;
	}
	if (is_string($val)) {
		return 'string['.strlen($val).']='.substr($val, 0, 16);
	}
	if (is_resource($val)) {
		return 'resource'.get_resource_type($val);
	}
	if (is_object($val)) {
		return 'object';
	}
	return '?';
}


/**
 * create a directory in TMPPATH and check if its writable
 */
function kataMakeTmpPath($dirname) {
	if (!file_exists(KATATMP.$dirname.DS)) {
		if (!mkdir(KATATMP.$dirname, 0770, true)) {
			throw new Exception("kataMakeTmpPath: cant create temporary path $dirname");
		}
	}
	if (!is_writable(KATATMP.$dirname)) {
		throw new Exception("kataMakeTmpPath: ".KATATMP."$dirname is not writeable");
	}
}


/**
 * load files from the from LIB-directory
 * @param string filename without .php
 */
function kataUse() {
	$args= func_get_args();
	foreach ($args as $arg) {
		require_once (LIB.strtolower($arg).'.php');
	}
}
