<?php
class SimpleTestExtension {
	public $tmpFiles = array();
	public $tmpDirs = array();

	function after($method){
			parent::after($method);
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
			if(isset($this->_reporter->reporter) && $this->_reporter->reporter instanceof MyHTMLReporter){
				$this->_reporter->reporter->paintCoverage("function");
			}else if($this->_reporter instanceof MyHTMLReporter){
				$this->_reporter->paintCoverage("function");
			}

	}
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
	function assertTextIn($needle,$haystack){
		$vorkommen = explode($needle,$haystack);
		if(count($vorkommen)==1){
			$this->assertTrue(false,$needle." ist nicht im gesuchten Text enthalten");
			return;
		}
		$this->assertTrue(true);
	}
	function assertTextNotIn($needle,$haystack){
		$vorkommen = explode($needle,$haystack);
		if(count($vorkommen)==1){
			$this->assertTrue(true);
			return;
		}
		$this->assertTrue(false,$needle." ist im gesuchten Text enthalten");
	}
}
?>
