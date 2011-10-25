<?php
/*
* kopiert kram von unstable zu stable, je nach parameter
*
* app     alle models,views,controller,lib,vendors
* lang    alle locale-files
* img     alle grafiken
* all     alles
*/
define('DS',DIRECTORY_SEPARATOR);

$what='';
if (isset($argv[1])) { $what=$argv[1]; } else {
   die("parameters: app lang img - all\n");
}

$here    = dirname(__FILE__).DS;
$testing = realpath($here."..".DS).DS;
$stable  = realpath($here."..".DS."..".DS."branches".DS."stable".DS).DS;

echo "source: $testing\ndest  : $stable\n\n";

cp($testing."kata".DS,$stable."kata".DS);

//////////////////////////////////////////////////////////////////

function hasParam($parm) {
         for ($i=1;$i<$argc;$i++) {
             if ($argv[$i] == $parm) {
                return true;
             }
         }
         return false;
}

function md($dir) {
	@mkdir($dir,0,true);
}

function fcp($sfile,$dfile) {
	copy($sfile,$dfile);
}

function cp($sdir,$ddir,$ext=false) {
	echo "$sdir\n";
	md($ddir);

	$d=scandir($sdir);
	foreach ($d as $f) {
		if (substr($f,0,1)=='.') {
			if ($f!='.htaccess') { continue; }
		}
		
		if (!is_dir($sdir.$f)) {
			if ($ext !== false) {
				if (substr($f,-strlen($ext),strlen($ext)) != $ext) {
					continue;
				}
			}
			fcp($sdir.$f,$ddir.$f);
		} else {
			cp( $sdir.$f.DS, $ddir.$f.DS);
		}
	}
}

?>
