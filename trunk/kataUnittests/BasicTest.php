<?php
require_once('lib'.DIRECTORY_SEPARATOR.'kataTestBase.simpleTest.class.php');

class BasicTest  extends kataTestBaseClass {

	function testInitialize() {
		//entfernt die Coverage Berechnung des Startens von den in den einzelnen Funktionen tatsächlich benutzten...
		//sonst hat erste Funktion mehr Coverage als eigentlich verbraucht.
		$this->bootstrapKata();
	}
	function testAm(){
		$this->assertIsA(am(),"array");
		$xy = array("x","y");
		$z = array("z");
		$xyz = array("x","y","z");
		$this->assertEqual(am($xy,$z),$xyz);
		$xy = array("x"=>"y");
		$xz = array("x"=>"z");
		$xyz = array("x"=>array("y","z"));
		//TODO sollte das gewollte Verhalten bei merge nicht eher dies sein: "array("x"=>array("y","z"));".
		$this->assertEqual(am($xy,$xz),$xz);
	}
	function testDebug(){
		$word = "<b>hallo</b>";
		ob_start();
		debug($word);
		$before = '<pre style="border:1px solid red;color:black;background-color:#e8e8e8;padding:3px;">';
		$after ='</pre>';
		$this->assertEqual(ob_get_clean(),$before."'".$word."'".$after);
		ob_start();
		debug($word,true);
		$before = '<pre style="border:1px solid red;color:black;background-color:#e8e8e8;padding:3px;">';
		$after ='</pre>';
		$this->assertEqual(ob_get_clean(),$before."'".htmlspecialchars($word)."'".$after);
	}

}


?>
