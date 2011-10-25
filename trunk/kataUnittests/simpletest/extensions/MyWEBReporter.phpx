<?php
require_once(dirname(__FILE__).'/../simpletest.php');

class MyWEBReporter extends SimpleReporter {

	private $HTMLoutput = "";
	private $flushCounter = 0;
	function MyWEBReporter(){

	}
	function setHTML($text){
		print_r($this->HTMLoutput.$text);
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
		</script>');
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
	}

    function paintFail($message) {
    	parent::paintFail($message);
        $this->setHTML("<span class=\"fail\">Fail</span>: ");
        $breadcrumb = $this->getTestList();
        array_shift($breadcrumb);
        $this->setHTML(implode(" -&gt; ", $breadcrumb));
        $this->setHTML(" -&gt; " . $this->_htmlEntities($message) . "<br />\n");
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
    }

    function paintException($exception) {
    	parent::paintException($exception);
        $this->setHTML("<span class=\"fail\">Exception</span>: ");
        $breadcrumb = $this->getTestList();
        array_shift($breadcrumb);
        $this->setHTML( implode(" -&gt; ", $breadcrumb));
        $message = 'Unexpected exception of type [' . get_class($exception) .
                '] with message ['. $exception->getMessage() .
                '] in ['. $exception->getFile() .
                ' line ' . $exception->getLine() . ']';
       $this->setHTML(" -&gt; <strong>" . $this->_htmlEntities($message) . "</strong><br />\n");
    }

    function paintSkip($message) {
    	parent::paintSkip($message);
        $this->setHTML("<span class=\"pass\">Skipped</span>: ");
        $breadcrumb = $this->getTestList();
        array_shift($breadcrumb);
        $this->setHTML( implode(" -&gt; ", $breadcrumb));
        $this->setHTML(" -&gt; " . $this->_htmlEntities($message) . "<br />\n");
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
