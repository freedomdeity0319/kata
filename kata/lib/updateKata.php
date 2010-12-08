<?php

/**
 * script to ease updates of kata
 * @author feldkamp@gameforge.de
 * @package kata_internal
 */

/**
 * include needed files
 */
require 'defines.php';
require 'boot.php';

function deleteIfExists($file) {
	if (file_exists($file)) {
		unlink($file);
		echo "removed \"".str_replace(ROOT, '', $file)."\"\n";
	}
}

///////////////////////////////////////////////////////////////////////////////

deleteIfExists(ROOT.'tmp'.DS.'cache'.DS.'dummy');
deleteIfExists(ROOT.'tmp'.DS.'logs'.DS.'dummy');
deleteIfExists(ROOT.'tmp'.DS.'sessions'.DS.'dummy');
deleteIfExists(ROOT.'config'.DS.'tags.php.default');
deleteIfExists(ROOT.'views'.DS.'layouts'.DS.'404.thtml.default');
deleteIfExists(ROOT.'views'.DS.'layouts'.DS.'error.thtml.default');
deleteIfExists(ROOT.'webroot'.DS.'favicon.ico.default');
deleteIfExists(ROOT.'webroot'.DS.'crossdomain.xml.default');
deleteIfExists(ROOT.'controllers'.DS.'components'.DS.'client.session.php');
deleteIfExists(ROOT.'controllers'.DS.'components'.DS.'file.session.php');
deleteIfExists(ROOT.'controllers'.DS.'components'.DS.'memcached.session.php');
deleteIfExists(ROOT.'controllers'.DS.'components'.DS.'locale.php');
deleteIfExists(ROOT.'controllers'.DS.'components'.DS.'session.php');
deleteIfExists(ROOT.'utilities'.DS.'cacheutility.php');
deleteIfExists(ROOT.'utilities'.DS.'imageutility.php');
deleteIfExists(ROOT.'utilities'.DS.'iputility.php');
deleteIfExists(ROOT.'utilities'.DS.'lockutility.php');
deleteIfExists(ROOT.'utilities'.DS.'validateutility.php');
deleteIfExists(ROOT.'views'.DS.'helpers'.DS.'html.php');
deleteIfExists(ROOT.'views'.DS.'helpers'.DS.'js.php');
deleteIfExists(ROOT.'views'.DS.'layouts'.DS.'error.thtml');
deleteIfExists(ROOT.'lib'.DS.'scaffold_controller.php');
deleteIfExists(ROOT.'lib'.DS.'app_controller.php');
deleteIfExists(ROOT.'lib'.DS.'app_model.php');
deleteIfExists(ROOT.'lib'.DS.'tags.php');
deleteIfExists(ROOT.'lib'.DS.'controllers'.DS.'katacache_controller.php');
deleteIfExists(ROOT.'lib'.DS.'databaseunshurewhattoreturn.php');

///////////////////////////////////////////////////////////////////////////////

/*
include (ROOT.'config'.DS.'tags.php');

$knownTags= array (
	'link',	'selectstart', 'selectmultiplestart', 'selectempty', 'selectoption', 'selectend', 'image'
);

foreach ($knownTags as $singleTag) {
	unset($tags[$singleTag]);
}

if (count($tags)>0) {
	echo "please update config/tags.php to look like this:\n<?php\n";
	foreach ($tags as $tagName=>$tagValue) {
		echo '$tags[\''.$tagName."']='".$tagValue."';\n";
	}
	echo "?>\n\n";
}

//deleteIfExists(ROOT.'config'.DS.'tags.php');
*/

///////////////////////////////////////////////////////////////////////////////

$divs= array (
	'SESSION_TIMEOUT',
	'SESSION_COOKIE',
	'SESSION_STORAGE',
	'SESSION_STRING',
	'CACHE_IDENTIFIER',

);

$config= file(ROOT.'config'.DS.'core.php');
foreach ($config as $configline) {
	foreach ($divs as $divnum => $div) {
		if (strpos($configline, $div) !== false) {
			unset ($divs[$divnum]);
			break;
		}
	}
}

if (count($divs)) {
	echo "The following defines are missing from your config/core.php\n";
	foreach ($divs as $div) {
		echo "-> $div\n";
	}
	echo "Take a look at config/core.php.default for reference.\n\n";
}

///////////////////////////////////////////////////////////////////////////////

echo "\n\nkataUpdater finished.\n";
