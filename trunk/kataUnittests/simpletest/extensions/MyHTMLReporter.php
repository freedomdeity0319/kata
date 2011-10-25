<?php
require_once(dirname(__FILE__).'/../simpletest.php');
require_once dirname(__FILE__). '/CodeCoverage/Renderer.php';

class MyHTMLReporter extends SimpleReporter {

	private $HTMLoutput = "";
	private $Coverage = 0;
	private $allCoverage = array();
	private $fCoverage = array();
	function MyHTMLReporter($coverage = 0){
		$this->Coverage = $coverage;
	}
	function setHTML($text){
		$this->HTMLoutput = $this->HTMLoutput.$text;
	}
	function getHTML(){
		return $this->HTMLoutput;
	}

	function multimerge($array1, $array2,$level=0) {
		if ($level == 0 && empty($array2)){
			return $array1;
		}
		$level++;
    	if (is_array($array2) && count($array2)) {
      		foreach ($array2 as $key => $value) {
        		if (is_array($value) && count($value)) {
        			if(isset($array1[$key])){
          				$array1[$key] = $this->multimerge($array1[$key], $value,$level);
        			}else{
        				$array1[$key] = $value;
        			}
        		} else {
          			$array1[$key] = $value;
        		}
      		}
    	}else{
    		$array1 = $array2;
    	}
    	return $array1;
  	}

	function getFullTrace($arr) {
		$output = '';

		foreach ($arr as $data) {
			$output.=$data['file'].':'.$data['line'].' '.$data['class'].$data['type'].$data['function'].'('.
			print_r($data['args'],true).")<br />";
		}

		return $output;
	}


	function sendNoCacheHeaders() {
        if (! headers_sent()) {
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
        }
    }
    function paintHeader($test_name) {
		$this->sendNoCacheHeaders();
		$this->setHTML("<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">");
        $this->setHTML("<html>\n<head>\n<title>$test_name</title>\n");
        $this->setHTML("<meta http-equiv=\"Content-Type\" content=\"text/html; charset=\"ISO-8859-1\">\n");
        $this->setHTML("<style type=\"text/css\">\n");
        $this->setHTML($this->_getCss() . "\n");
       	$this->setHTML("</style>\n");
        $this->setHTML("</head>\n<body>\n");
        $this->setHTML("<h1>$test_name</h1>\n");
		$this->setHTML('<script language="javascript">
			function toggle(that){
				if(that.style.display == "block"){
					that.style.display = "none";
				}else{
					that.style.display = "block";
				}
		}
		</script>;');
    }

    function paintFooter($test_name) {
    	$colour = ($this->getFailCount() + $this->getExceptionCount() > 0 ? "red" : "green");
        $this->setHTML("<div style=\"");
        $this->setHTML("padding: 8px; margin-top: 1em; background-color: $colour; color: white;");
        $this->setHTML("\">");
        $this->setHTML($this->getTestCaseProgress() . "/" . $this->getTestCaseCount());
        $this->setHTML(" test cases complete:\n");
        $this->setHTML("<strong>" . $this->getPassCount() . "</strong> passes, ");
        $this->setHTML("<strong>" . $this->getFailCount() . "</strong> fails and ");
        $this->setHTML("<strong>" . $this->getExceptionCount() . "</strong> exceptions.");
        $this->setHTML("</div>\n");
        $this->setHTML("</body>\n</html>\n");
        print_r($this->getHtml());
        $this->paintCoverage("end");
    }

    function paintStart($test_name, $size) {
        parent::paintStart($test_name, $size);
    }


	function paintMessage($message){
		 $this->setHTML($message);
	}


    function paintEnd($test_name, $size) {
    	parent::paintEnd($test_name, $size);
    }


	function paintError($message){
		parent::paintError($message);
		$this->setHTML("<span class=\"fail\">Exception</span>: ");
        $breadcrumb = $this->getTestList();
        array_shift($breadcrumb);
        $this->setHTML( implode(" -&gt; ", $breadcrumb));
        $this->setHTML(" -&gt; <strong>" . $this->_htmlEntities($message) . "</strong><br />\n");
		$this->paintCoverage();
	}


	function paintCoverage($final=null){
		if (function_exists("xdebug_start_code_coverage")) {
			if($final == "end"){
				if($this->Coverage>0){
					$this->allCoverage = $this->multimerge($this->allCoverage,xdebug_get_code_coverage());
					$cc =  PHPUnit2_Util_CodeCoverage_Renderer::factory('HTML',array('tests' => $this->allCoverage));
					print_r($cc->render());
					xdebug_stop_code_coverage();
				}
			}else if($final == "function"){
				if($this->Coverage>1){
					$this->allCoverage = $this->multimerge($this->allCoverage,xdebug_get_code_coverage());
					$this->fCoverage = $this->multimerge($this->fCoverage,xdebug_get_code_coverage());
					$cc =  PHPUnit2_Util_CodeCoverage_Renderer::factory('HTML',array('tests' => $this->fCoverage));
					$this->setHTML(($cc->render()));
					$this->fCoverage = array();
					xdebug_stop_code_coverage();
					xdebug_start_code_coverage();
				}
			}else if(empty($final)){
				if($this->Coverage>2){
    				$cc =  PHPUnit2_Util_CodeCoverage_Renderer::factory('HTML',array('tests' => xdebug_get_code_coverage()));
    				$this->setHTML(($cc->render()));
    				$this->allCoverage = $this->multimerge($this->allCoverage,xdebug_get_code_coverage());
    				$this->fCoverage = $this->multimerge($this->fCoverage,xdebug_get_code_coverage());
    				xdebug_stop_code_coverage();
    				xdebug_start_code_coverage();
				}
			}
		}
	}

    function paintPass($message) {
    	parent::paintPass($message);
        $this->setHTML("<span class=\"pass\">Pass</span>: ");
        $breadcrumb = $this->getTestList();
        array_shift($breadcrumb);
        if(isset($breadcrumb[2])){
        	$this->setHTML($breadcrumb[2]."->".$breadcrumb[3]."<br />");
        }else{
        	$this->setHTML($breadcrumb[0]."->".$breadcrumb[1]."<br />");
        }
		$this->paintCoverage();
    }

    function paintFail($message) {
    	parent::paintFail($message);
        $this->setHTML("<span class=\"fail\">Fail</span>: ");
        $breadcrumb = $this->getTestList();
        array_shift($breadcrumb);
        $this->setHTML(implode(" -&gt; ", $breadcrumb));
        $this->setHTML(" -&gt; " . $this->_htmlEntities($message) . "<br />\n");
        $this->paintCoverage();
    }

    function paintException($exception) {
		ini_set('xdebug.var_display_max_depth',16);
		ini_set('xdebug.var_display_max_data',255);
		ini_set('xdebug.var_display_max_children',16);

    	parent::paintException($exception);
        $this->setHTML("<span class=\"fail\">Exception</span>: ");
        $breadcrumb = $this->getTestList();
        array_shift($breadcrumb);
        $this->setHTML( implode(" -&gt; ", $breadcrumb));
        $message = 'Unexpected exception of type [' . get_class($exception) .
                '] with message ['. $exception->getMessage() .
                '] in ['. $exception->getFile() .
                ' line ' . $exception->getLine() . ']';
		ob_start(); xdebug_var_dump($exception->getTrace()); $trace = ob_get_clean(); //$this->getFullTrace($exception->getTrace());
       $this->setHTML(" -&gt; <strong>" . $this->_htmlEntities($message) . "</strong><br /><pre>".$trace."</pre><br />\n");
       $this->paintCoverage();
    }

    function paintSkip($message) {
    	parent::paintSkip($message);
        $this->setHTML("<span class=\"pass\">Skipped</span>: ");
        $breadcrumb = $this->getTestList();
        array_shift($breadcrumb);
        $this->setHTML( implode(" -&gt; ", $breadcrumb));
        $this->setHTML(" -&gt; " . $this->_htmlEntities($message) . "<br />\n");
        $this->paintCoverage();
    }

    function paintFormattedMessage($message) {
        $this->setHTML('<pre>' . $this->_htmlEntities($message) . '</pre>');
    }

    function _getCss() {
        return ".fail { background-color: inherit; color: red; }" .
                ".pass { background-color: inherit; color: green; }" .
                " pre { background-color: lightgray; color: inherit; }".
	'td.ccLineNumber, td.ccCoveredLine, td.ccUncoveredLine, td.ccNotExecutableLine {
		font-family: monospace;
		white-space: pre;
	}
 	td.ccLineNumber, td.ccCoveredLine {
		background-color: #aaaaaa;
	}

	td.ccNotExecutableLine {
 	color: #aaaaaa;
	}
	.hidden {
		display:none;
	}
	body {
		font-size:12px;
		line-height:12px;
	}
	';
    }

    function _htmlEntities($message) {
        return htmlentities($message, ENT_COMPAT, "ISO-8859-1");
    }
}

?>
