<?php
/**
 * never change this file!
 *
 * @package kata
 * @author mnt@codeninja.de
 */


/**
 * setup always needed paths
 */
require('..'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'defines.php');

/**
 * something simple we can return on our own?
 */
if (isset($_GET['kata'])) {
	switch ($_GET) {
		case 'favicon.ico':
			header('Content-Type: image/x-icon');
			readfile(WWW_ROOT.'favicon.ico');
			die(); 
			break;
		case 'crossdomain.xml':
			header('Content-Type: text/xml');
			readfile(WWW_ROOT.'crossdomain.xml');
			die();
			break;
	}
}	

/**
 * include needed files
 */
require(LIB."boot.php");

/**
 * call dispatcher to handle the rest
 */
$dispatcher=new dispatcher();
echo $dispatcher->dispatch(isset($_GET['kata'])?$_GET['kata']:'',isset($routes)?$routes:null);
?>
