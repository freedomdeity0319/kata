<?php
/**
 * extension for temporary files and browser-output
 *
 * @package katatest_lib
 * @author Dietmar Riess
 */


/**
 * interface for extension class
 * @package katatest_lib
 * @author Dietmar Riess
 */
interface SimpleTestExtensionInterface {
	function after($method);
	function createTmpFile($name,$content);
	function assertContain($needle,$haystack,$message=null);
	function assertNotContain($needle,$haystack,$message=null);
	function replaceTmpFile($name,$content,$replace);
}

/**
 * simple test extension class 
 * 
 * @package katatest_lib
 * @author Dietmar Riess
 */
class SimpleTestExtension {
	public $tmpFiles = array();
	public $tmpReplaces = array();
	public $tmpDirs = array();
	public $caller =null;
	private $errorLevel = '';

	function __construct($caller){
		$this->caller = $caller;
	}
	/**
	 * führt nach jedem Test aufräum aktionen durch
	 * es werden Temporäre Dateien wieder gelöscht
	 */
	function after($method){
			foreach ($this->tmpFiles as $name=>$content){
				unset($this->tmpFiles[$name]);
				if(file_exists(ROOT.$name)){
					unlink(ROOT.$name);
				}
			}
			foreach($this->tmpDirs as $key =>$dir){
				unset($this->tmpDirs[$key]);
				rmdir($dir);
			}
			foreach($this->tmpReplaces as $name=>$replaces){
				$replace = array_pop($replaces);
				while($replace){
					if(file_exists(ROOT.$name)){
						$text = file_get_contents(ROOT.$name);
						$newText = str_replace($replace["replace"],$replace["content"],$text);
						file_put_contents(ROOT.$name,$newText);
					}
					$replace = array_pop($replaces);
				}
				unset($this->tmpReplaces[$name]);
			}
			if(isset($this->caller->_reporter->reporter) && $this->caller->_reporter->reporter instanceof MyHTMLReporter){
				$this->caller->_reporter->reporter->paintCoverage("function");
			}else if($this->caller->_reporter instanceof MyHTMLReporter){
				$this->caller->_reporter->paintCoverage("function");
			}

	}
	/**
	 * erstellt Temporäre Dateien und Pfade //benötigt ausreichend Schreibrechte auf den Ordnern
	 */
	function createTmpFile($name,$content){
		$teile = explode("/",$name);
		array_pop($teile);
		$path = ROOT;
		foreach($teile as $teil){
			$path = $path."/".$teil;
			if(!is_dir($path) && !empty($path)){
				mkdir($path);
				$this->tmpDirs[] = $path;
			}
		}
		$h = fopen (ROOT.$name,"w");
		if ($h) {
			fwrite($h,$content);
			$this->tmpFiles[$name]=$content;
			fclose($h);
		}
	}
	/**
	 * ersetzt TemporÃ¤r in einer Datei einen Inhalt durch einen anderen
	 */
	function replaceTmpFile($name,$content,$replace){
		if(file_exists(BASEPATH.$name)){
			$text = file_get_contents(BASEPATH.$name);
			if($text === false){
				return;
			}
			$newText = str_replace($content,$replace,$text);
			if(!isset($this->tmpReplaces[$name])){
				$this->tmpReplaces[$name] = array();
			}
			$this->tmpReplaces[$name][]=array("content"=>$content,"replace"=>$replace);
			file_put_contents(BASEPATH.$name,$newText);
		}
	}
	/**
	 * überprüft ob ein Text in einem anderen enthalten ist
	 */
	function assertContain($needle,$haystack,$message=null){
		$vorkommen = explode($needle,$haystack);
		if(count($vorkommen)==1){
			if(!isset($message)){
				$message = $needle." ist nicht im gesuchten Text enthalten";
			}
			$this->caller->assertTrue(false,$message);
			return;
		}
		$this->caller->assertTrue(true);
	}
	/**
	 * überprüft ob ein Text nicht in einem anderen enthalten ist
	 */
	function assertNotContain($needle,$haystack){
		$vorkommen = explode($needle,$haystack);
		if(count($vorkommen)==1){
			$this->caller->assertTrue(true);
			return;
		}
		if(!isset($message)){
			$message = $needle." ist im gesuchten Text enthalten";
		}
		$this->caller->assertTrue(false,$message);
	}
	
	/**
	 * text-output an browser (darf html enthalten) 
	 */
	function sendMessage($msg){
		$return = '';
		if(is_array($msg)){
			foreach ($msg as $key=>$value){
				$return .= $key."=>(".$this->sendMessage($value).")";
			}
		}else{
			$return = $msg;
		}
		return $return;
	}
}
