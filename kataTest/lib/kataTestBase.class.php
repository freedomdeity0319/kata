<?php
/**
 * enhances the PHPUnit base functionality with some
 * nice http-functions
 *
 * @package katatest_lib
 * @author mnt@codeninja.de
 */


/**
 * requirements
 */
require dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'myvars.php';
require_once ('functions_http.php');
require_once ('functions_parser.php');

/**
 * base class that enhances PHPUnit with http-functionality
 *
 * @author mnt@codeninja.de
 * @package katatest_lib
 */
class kataTestBaseClass extends PHPUnit_Framework_TestCase {

	/**
	 * placeholder for our http-request class
	 * @var object
	 */
	public $_http; // http-request class

	/**
	 * placeholder for our html-parser class
	 * @var object
	 */
	public $_html; // html-parser class

	/**
	 * should we automatically follow http 301 redirs?
	 * @var bool
	 */
	private $_redir= true;

	/**
	 * how verbose should we be? 0=silent, 1=a little, 2=full blow
	 * @var int verboseness
	 */
	private $verbose= 0;


/**
 * strings that should not appear in the polled html unless something went wrong
 * @var array
 */
	public $_errorstrings= array (
		'<?php',
		'<?=',
		'<? ',
		'Warning: ',
		'Fatal error: ',
		'Notice: ',
		'Use of undefined constant ',
		'---UNSET(',
		'404 Not Found',
		'Framework Exception',
		//	"';",
	//	'";',
	'<foo>',
		'Array (',


	);

/**
 * construct everything needed
 */
	function __construct() {
		if (!defined('BASEURL')) {
			$base_url= getenv('BASEURL');
			if (empty ($baseUrl)) {
				die('You need to set the environment variable BASEURL to the http-root of your framework');
			}
			define('BASEURL', $baseUrl);
		}

		if (!defined('BASEPATH')) {
			$basePath= getenv('BASEPATH');
			if (empty ($basePath)) {
				die('You need to set the environment variable BASEPATH the the file-root of your framework');
			}
			define('BASEPATH', $basePath);
		}

		// clear apc-cache, so we have the newest language-files
		if (function_exists('apc_clear_cache')) {
			apc_clear_cache();
			apc_clear_cache('user');
		}

		parent :: __construct();
		$this->_redir= true;
		$this->_http= & new HTTPTester();
		$this->_http->setFollowRedirects(false);

		if (defined('AUTH_USER') && (AUTH_USER != '')) {
			$this->_http->setAuth(AUTH_USER, AUTH_PWD);
		}

		$this->_html= & new FormParser();
		$this->_html->setBaseURL(BASEURL);

		/*
		__defineOrEnv('MYSQL_HOST');
		__defineOrEnv('MYSQL_USER');
		__defineOrEnv('MYSQL_PASS');
		__defineOrEnv('MYSQL_DB');

				if (defined(MYSQL_HOST) && (MYSQL_HOST != '')) {
					mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS);
					mysql_select_db(MYSQL_DB);
					$this->assertTrue(mysql_ping(), "No database connection, set MYSQL_HOST,MYSQL_USER,MYSQL_PASS,MYSQL_DB environment variables!");
				}
		*/

	}

/**
 * turn environment variables into defines
 * @param string $name name of the env variable
 * @param string $value value to set to. can be false
 */
	function __defineOrEnv($name, $value= false) {
		$setto= getenv($name);
		if (empty ($setto)) {
			define($name, $value);
		} else {
			if ($setto !== false) {
				define($name, $setto);
			}
		}
	}


	/**
	 * @param bool automatically do a GET if we receive a redirect (true) or simply return the page with the redirect on it
	 */
	public function setFollowRedirects($param) {
		$this->_redir= $param;
	}


/**
 * set verboseness. 0=nothing 1=some 2=extensive
 * @param int $level
 */
	function setVerbose($level) {
		if (($level >= 0) && ($level <= 2))
			$this->verbose= $level;
	}

	/////////////////////////////////////////////////////////////////////
	////KATA FUNCTIONS //////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////

/**
 * wheter or not we did initialize the kata framework yet
 * @var bool
 */
	private $kataInitialized= false;

	/**
	 * bootstrap kata if not already done
	 */
	public function bootstrapKata() {
		if (!$this->kataInitialized) {
			require_once (BASEPATH.'lib'.DIRECTORY_SEPARATOR.'defines.php');
			require_once (LIB.'boot.php');
			$this->kataInitialized= true;
		}
	}

/**
 * load the given model from kata
 * @param string $name name of the model, eg 'User'
 */
	public function loadModel($name) {
		$this->bootstrapKata();

		if (!class_exists($name)) {
			loadModel($name);
		}
		$this-> $name= & new $name;
	}

/**
 * load the given controller from kata and initialize it. you cant dispatch any url, use get() or post() for this.
 * @param string $name name of the controller, eg 'Main'
 */
	public function loadController($name) {
		$this->bootstrapKata();

		require ROOT.'controllers'.DS.strtolower($name).'_controller.php';

		$name= $name.'Controller';

		$controller = new $name;
		$this->$name= $controller;
		$controller->_constructClasses();
		$controller->_base = BASEURL;
		$tempUrl = explode('/',BASEURL);
		array_shift($tempUrl);
		array_shift($tempUrl);
		array_shift($tempUrl);
		$controller->_basePath = '/'.implode('/',$tempUrl);
		$controller->webroot = $controller->base.'webroot/';
	}

	/////////////////////////////////////////////////////////////////////
	////ASSERT FUNCTIONS ////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////

	/**
	 * return if given link can be found on current page
	 *
	 * @param string $link kata-base relative url (read: without http)
	 * @return bool if link was found on current page
	 */
	public function containsLink($link) {
		$found= false;
		foreach ($this->_html->getLinks() as $alllink) {
			if ($alllink['href'] == $link) {
				$found= true;
				break;
			}
		}

		return $found;
	}

	/**
	 * return if given text can be found on current page
	 *
	 * @param string $text text to search for
	 * @return bool if text was found on current page
	 */
	public function containsText($text) {
		return strpos($this->_http->getBody(), $text) !== false;
	}

	/**
	 * must have link
	 *
	 * @param string $link kata-base relative url (read: without http)
	 */
	public function mustHaveLink($link) {
		$this->assertTrue($this->containsLink($link), 'Could not find link: '.$link);
	}

	/**
	 * must not have link
	 *
	 * @param string $link kata-base relative url (read: without http)
	 */
	public function mustNotHaveLink($link) {
		foreach ($this->_html->getLinks() as $alllink) {
			$this->assertNotEquals($alllink['href'], $link, 'Found unexpected link: '.$link);
		}
	}

	/**
	 * search all pages (?page=) for text and link and fail if nowhere to be found
	 *
	 * @param string $url kata-base relative url (read: without http)
	 * @param string $mustHaveLink kata-base relative url (read: without http)
	 * @param string $mustHaveText
	 * @param array $expectedPages optional: array with page numbers we expect
	 * @return bool
	 */
	function mustHaveTextAndLink($url, $mustHaveLink, $mustHaveText, $expectedPages= null) {
		return $this->mustAnything($url, $mustHaveLink, $mustHaveText, null, null, $expectedPages);
	}
	/**
	 * search all pages (?page=) for text and link and fail if somewhere found
	 *
	 * @param string $url kata-base relative url (read: without http)
	 * @param string $mustNotHaveLink kata-base relative url (read: without http)
	 * @param string $mustNotHaveText
	 * @param string $expectedPages optional: array with page numbers we expect
	 * @return bool
	 */
	function mustNotHaveTextOrLink($url, $mustNotHaveLink, $mustNotHaveText, $expectedPages= null) {
		return $this->mustAnything($url, null, null, $mustNotHaveLink, $mustNotHaveText, $expectedPages);
	}

	/**
	 * convenience function: Do everything in one go: check for links and texts on the page specified by $url, and do the same for (possibly) following pages (?page=)
	 * mustHave will fail only for less then one page
	 * mustNotHave will fail on any page
	 *
	 * @param string $url kata-base relative url (read: without http)
	 * @param string $mustHaveLink kata-base relative url (read: without http)
	 * @param string $mustHaveText
	 * @param string $mustNotHaveLink kata-base relative url (read: without http)
	 * @param string $mustNotHaveText
	 * @param array $expectedPages optional: array with page numbers we expect
	 * @return bool
	 */
	public function mustAnything($url, $mustHaveLink, $mustHaveText, $mustNotHaveLink, $mustNotHaveText, $expectedPages= null) {

		$linkFound= ($mustHaveLink == NULL);
		$textFound= ($mustHaveText == NULL);

		while (true) {
			$this->get($url);

			if ($mustHaveLink != NULL) {
				$linkFound= $linkFound || $this->containsLink($mustHaveLink);
			}
			if ($mustHaveText != NULL) {
				$textFound= $textFound || $this->containsText($mustHaveText);
			}
			if ($mustNotHaveLink != NULL) {
				$this->mustNotHaveLink($mustNotHaveLink);
			}
			if ($mustNotHaveText != NULL) {
				$this->mustNotHaveText($mustNotHaveText);
			}

			// generate page and re-set url
			$page= 1;
			if (strpos($url, '?page=') !== false) {
				$temp= explode('?page=', $url);
				$page= $temp[1];
				$url= $temp[0];
			}
			$url= $url.'?page='. ($page +1);

			// is there a next page?
			if (!$this->containsLink($url)) {
				break;
			}
		}

		$this->assertTrue($linkFound, "Could not find link '$mustHaveLink' on any page of '$url'");
		$this->assertTrue($textFound, "Could not find text '$mustHaveText' on any page of '$url'");

		if ($expectedPages != null) {
			$this->assertEquals($expectedPages, $page, "Expected $expectedPages pages, but found $page pages");
		}
	}

	/**
	 * must have form or fail
	 *
	 * @param string $action kata-base relative url of the form (read: without http)
	 */
	public function mustHaveForm($action) {
		$allforms= $this->_html->getForms();
		$found= false;

		$this->assertTrue(@ is_array($allforms[$action]), 'Could not find form action: '.$action);
	}

	/**
	 * must not have form or fail
	 *
	 * @param string $action kata-base relative url of the form (read: without http)
	 */
	public function mustNotHaveForm($action) {
		$allforms= $this->_html->getForms();
		$found= false;

		$this->assertFalse(@ is_array($allforms[$action]), 'found unexcepted form: '.$action);
	}

/**
 * must have given text or fail
 *
 * @param string $text
 */
	public function mustHaveText($text) {
		$url= $this->_http->getURL();
		$this->assertContains($text, $this->_http->getBody(), "Could not find text '$text' on url '$url'");
	}

/**
 * must not have given text or fail
 *
 * @param string $text
 */
	public function mustNotHaveText($text) {
		$url= $this->_http->getURL();
		$this->assertNotContains($text, $this->_http->getBody(), "Should not have text: '$text' found on url '$url'");
	}

	/**
	 * to test if redirects really redirect or fail
	 *
	 * @param string $url kata-base relative url (read: without http)
	 */
	public function weMustLandAtURL($url) {
		$landurl= $this->_http->getURL();
		if (substr($landurl, -1, 1) == '/') {
			$landurl= substr($landurl, 0, strlen($landurl) - 1);
		}

		$this->assertEquals($landurl, BASEURL.$url, 'We expected to land at: ['.BASEURL.$url.'] We landed at: ['.$landurl.']');
	}

	/**
	 * Finds out if $link is found more than once or fail
	 *
	 * @param string $link kata-base relative url (read: without http)
	 */
	public function checkIfDuplicateLink($link) {
		$counter= 0;
		foreach ($this->_html->getLinks() as $alink) {
			if ($alink['href'] == $link) {
				$counter++;
			}
		}
		$this->assertTrue($counter < 2, "We should not find link '".$link."' more than once!");
	}

	/**
	 * get kata-url of a link by link-text
	 *
	 * @param string $text text between <a> brackets
	 * @return mixed false when not found, kataURL if found
	 */
	public function getLinkTarget($text) {
		$links= $this->_html->getLinks();
		$r= false;

		foreach ($links as $link) {
			if ($link['a'] == $text) {
				$r= $link['href'];
				break;
			}
		}

		return $r;
	}

/**
 * return all variables of the given form
 *
 * @param string $formname kata-base relative url of the form (read: without http)
 * @return array variables or empty array if form not found
 */
	public function getFormVars($formname) {
		$forms= $this->_html->getForms();
		if (isset ($forms[$formname])) {
			return $forms[$formname];
		}
		return array ();
	}

	/////////////////////////////////////////////////////////////////////
	////TEXT UTILITY FUNCTIONS///////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////

	/**
	 * cut out a text depending on a searchpattern.
	 * example: text is "abcdefghij",
	 * 			searchpattern is "ab*fg",
	 * 			then result is "cde"
	 *
	 * @param string $text text to search in
	 * @param string $pattern pattern to search in text
	 * @return mixed false when nothing is found, otherwise an array with text-results
	 */
	public function getPattern($text, $pattern, $casesensitive= true) {
		if ($text === false) {
			$text= $this->_http->getBody();
		}
		$pattern= explode("*", $pattern);

		if ($casesensitive) {
			$x1= strpos($text, $pattern[0]);
		} else {
			$x1= stripos($text, $pattern[0]);
		}
		if ($x1 === false) {
			return false;
		}

		if ($casesensitive) {
			$x2= strpos($text, $pattern[1], $x1);
		} else {
			$x2= stripos($text, $pattern[1], $x1);
		}
		if ($x2 === false) {
			return false;
		}

		return substr($text, $x1 +strlen($pattern[0]), $x2 - $x1 -strlen($pattern[0]));
	}

/**
 * cut out text depending on pattern, multiple wildcards allowed
 * example: text is "abcdefghij",
 * 			searchpattern is "ab*fg*ij",
 * 			then result is array("cde","h")
 *
 * @param string $text text to search in
 * @param string $pattern pattern to search in text
 * @return mixed false when nothing is found, otherwise an array with text-results
 */
	public function getPatterns($text, $pattern, $casesensitive= true) {
		$pattern= explode("*", $pattern);
		$out= array ();
		for ($i= 0; $i < count($pattern); $i++) {
			$temp= $this->getPattern($text, $pattern[$i -1].'*'.$pattern[$i], $casesensitive);
			if ($temp !== false) {
				$out[]= $temp;
			}
		}
		return $out;
	}

	//// SQL FUNCTIONS///////////////////////////////////////////////////
	// nothing yet, simply use a kata-model

	/////////////////////////////////////////////////////////////////////
	////HTTP UTILITY FUNCTIONS //////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////

	/**
	 * remove cookies etc. from session so http thinks this is a brand new connection
	 */
	public function clearSession() {
		if ($this->verbose) {
			echo "CLEAR SESSION\n";
		}
		$this->_http->clearSession();
	}

/**
 * checks if the content type says it's html. also checks for html-tag if application/* is returned
 * @return bool true if the request returned html
 */
	public function isHtml() {
		$headers= $this->_http->getHeaders();
		if (empty ($headers['content-type'])) {
			throw new Exception('No content-type given from server. thats baaad');
		}
		if (strtolower(substr($headers['content-type'], 0, 4)) == 'text') {
			return true;
		}
		if (strtolower(substr($headers['content-type'], 0, 11)) == 'application') {
			if (stripos($this->_http->getBody(), '</html>') !== false) {
				throw new Exception('content-type says document is application/x, but </html> tag found');
			}
		}
	}

/**
 * GET the given url and do some tests on it
 *
 * @param string $url ("foo/bar" for example)
 */
	public function get($url) {
		global $cnt;

		if (false === strpos($url, '://')) {
			$url= BASEURL.$url;
		}
		if ($this->verbose) {
			echo "GET $url\n";
		}
		$this->_http->GETRequest($url);
		$body= $this->_http->getBodyWithoutJS();
		$this->_html->parse($body);

		if ($this->isHtml()) {
			foreach ($this->_errorstrings as $s) {
				$this->mustNotHaveText($s);
			}
		}

		if ($this->verbose > 1) {
			$this->dumpWebsiteHtml('logs'.DIRECTORY_SEPARATOR.$this->getName().'_'.$cnt++.'.htm');
		}
	}

/**
 * POST the given URL with given POST-parameters and do some tests on it.
 * simulates correct RFC-behaviour by doing a GET if we received a redirect
 * after POST
 *
 * @param string $url ("foo/bar" for example)
 * @param array $postvars
 */
	public function post($url, $postvars) {
		global $cnt;

		if (false === strpos($url, '://')) {
			$url= BASEURL.$url;
		}
		if ($this->verbose) {
			echo "POST $url\n";
			print_r($postvars);
		}
		$this->_http->setPost($postvars);
		$this->_http->POSTRequest($url);
		$body= $this->_http->getBodyWithoutJS();
		$this->_html->parse($body);

		if ($this->isHtml()) {
			foreach ($this->_errorstrings as $s) {
				$this->mustNotHaveText($s);
			}
		}

		/* always do a GET if we get redirected (thats correct RFC behaviour) even
		 * if we did a POST request before.
		 * HTTP_Request does it wrong, so we do it here ourselfes.
		 */
		$headers= $this->_http->getHeaders();
		if (($this->_redir) && !empty ($headers['location'])) {
			$target= $headers['location'];
			if (substr($target, 0, strlen(BASEURL)) == BASEURL) {
				$target= substr($target, strlen(BASEURL));
			}
			$this->get($target);
			return;
		}

		if ($this->verbose > 1) {
			$this->dumpWebsiteHtml('logs'.DIRECTORY_SEPARATOR.$this->getName().'_'.$cnt++.'.htm');
		}
	}

	/**
	 * log in to framework. creates a session-cookie and polls the login-form
	 * to enable this test to get past any XSRF-protection
	 *
	 * @param string $user
	 * @param string $pass
	 */
	public function login($user, $pass) {
		if ($this->verbose) {
			echo "LOGIN $user $pass\n";
		}

		// we can only log in if we already started a session. if we cant find the sessioncookie, start a dummy-request to get one.
		$sessioncookie= $this->_http->getCookie(SESSIONCOOKIE);
		if (($sessioncookie === false) || empty ($sessioncookie['value'])) {
			$this->get('main/index');
		}
		$formVars= $this->getFormVars(LOGIN_FORM_URL);
		$formVars[LOGIN_FORM_USERNAME_NAME]= $user;
		$formVars[LOGIN_FORM_PASSWORD_NAME]= $pass;
		$this->post(LOGIN_FORM_URL, $formVars);
	}

	/**
	 * sets cookies that are used for any request
	 *
	 * @param array $param key-value pair for cookies
	 */
	public function assocCookies(& $param) {
		$new= array ();
		foreach ($param as $cookie) {
			$new[$cookie['name']]= $cookie;
		}
		return $new;
	}

	//////////////////////////////////////////////////////////////////
	/////// LOG FUNCTIONS ////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////

/**
 * dump the current page (including headers, cookies etc) into given file
 * @param string $filename
 */
	public function dumpWebsiteHtml($filename) {
		$filename= str_replace(array (
			'/',
			'?'
		), '_', $filename);
		if (substr($filename, -4) != '.log') {
			$filename= $filename.'.log';
		}

		file_put_contents('logs'.DIRECTORY_SEPARATOR.basename($filename), $this->_http->getBody().
		"---------------------------------------\n\n\nwe landed at: ".$this->_http->getURL()."\n".
		"\n\npost-data:\n".print_r($this->_http->getPost(), true).
		"\n\nheaders:\n".print_r($this->_http->getHeaders(), true).
		"\n\ncookies:\n".print_r($this->_http->getCookies(), true));
	}

/**
 * remove the given log
 * @param string $filename
 */
	public function killLog($filename= 'unittest.log') {
		unlink('logs'.DIRECTORY_SEPARATOR.basename($filename));
	}

/**
 * put given text in the given logfile
 * @param string $txt
 * @param string $filename
 */
	public function log($txt, $filename= 'unittest.log') {
		$h= fopen('logs'.DIRECTORY_SEPARATOR.basename($filename), 'a+');
		if ($h) {
			fwrite($h, trim($txt)."\n");
			fclose($h);
		}
	}

} //class
?>
