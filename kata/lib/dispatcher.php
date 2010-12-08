<?php


/**
 * Contains the dispatcher-class. Here is where it all starts.
 *
 * Kata - Lightweight MVC Framework <http://www.codeninja.de/>
 * Copyright 2007-2008, mnt@codeninja.de
 *
 * Licensed under The GPL License
 * Redistributions of files must retain the above copyright notice.
 * @package kata
 */

/**
 * dispatcher. this is the first thing that is constructed.
 * the dispatcher then collects all parameters given via get/post and instanciates the right controller
 * @package kata
 */
class dispatcher {
	/**
	 * placeholer-array for all relevant variables a class may need later on (e.g. controller)
	 * [isAjax] => false (boolean, tells you if view got called with /ajax/)
	 * [url] => Array (
	 *       [url] => locations
	 *       [foo] => bar (if url read ?foo=bar)
	 * )
	 * [form] => Array (
	 * 	  (all post-variables, automatically dequoted if needed)
	 * )
	 * [controller] => main (name of the controller of this request)
	 * [action] => index (name of the view of this request)
	 * @var array
	 */
	public $params;

	/**
	 * name of the current controller
	 * @var string
	 */
	public $controller;

	/**
	 * time in us the dispather started (used to calculate how long the framework took to completely render anything)
	 */
	private $starttime;

	/**
	 * constructor, just initializes starttime
	 */
	function __construct() {
		$this->starttime= microtime(true);
	}

	/**
	 * destructor, outputs Total Render time if DEBUG>0
	 */
	function __destruct() {
		if (DEBUG > 0) {
			kataDebugOutput('Total Render Time (including Models) '. (microtime(true) - $this->starttime).' secs');
			kataDebugOutput('Memory used '.number_format(memory_get_usage(true)).' bytes');
			kataDebugOutput('Parameters '.print_R($this->params, true));
			if (function_exists('xdebug_get_profiler_filename')) {
				$fn= xdebug_get_profiler_filename();
				if (false !== $fn) {
					kataDebugOutput('profilefile:'.$fn);
				}
			}
			kataDebugOutput('Loaded classes: '.implode(' ', array_keys(classRegistry :: getLoadedClasses())));
		}
	}

	/**
	 * start the actual mvc-machinery
	 * 1. constructs all needed params by calling constructParams
	 * 2. loads the controller
	 * 3. sets all needed variables of the controller
	 * 4. calls constructClasses of the controller, which in turn constructs all needed models and components
	 * 5. render the actual view and layout (if autoRender is true)
	 * 6. return the output
	 * @param string $url raw url string passed to the array (eg. /main/index/foo/bar)
	 */
	final function dispatch($url, $routes= null) {
		$this->constructParams($url, $routes);

		try {
			$lowername= strtolower($this->params['controller']);

			if ('appcontroller' == $lowername) {
				$this->fourohfour();
				return;
			}

			kataUse('controller');
			if (file_exists(ROOT.'controllers'.DS.$lowername.'_controller.php')) {
				require (ROOT.'controllers'.DS.$lowername.'_controller.php');
			} else {
				if (file_exists(LIB.'controllers'.DS.$lowername.'_controller.php')) {
					require (LIB.'controllers'.DS.$lowername.'_controller.php');
				} else {
					$this->fourohfour();
					return '';
				}
			}

			$classname= ucfirst($lowername).'Controller';

			// no need for class_registry usage here, as we can only have 1 controller
			$c= new $classname;
			$this->controller= $c;

			$c->basePath= $this->constructBasePath();
			$c->base= $this->constructBaseUrl();
			$c->webroot= $c->base.'webroot/';
			$c->params= & $this->params;
			$c->action= $this->params['action'];

			if (!is_callable(array (
					$c,
					$this->params['action']
				)) || ($this->params['action'] { 0 }
				== '_')) {
				$this->fourohfour();
				return;
			}

			$c->_constructClasses();
			if (!empty ($this->params['isAjax'])) {
				$c->layout= null;
			}

			$c->beforeAction();

			call_user_func_array(array (
				& $c,
				$this->params['action']
			), empty ($this->params['pass']) ? null : $this->params['pass']);

			if ($c->autoRender) {
				$c->render($this->params['action']);
			}
		} catch (Exception $e) {
			$basePath= $this->constructBasePath();
			if (file_exists(ROOT."views".DS."layouts".DS."error.thtml")) {
				include ROOT."views".DS."layouts".DS."error.thtml";
			} else {
				include LIB."views".DS."layouts".DS."error.thtml";
			}
			return '';
		}

		if ($this->params['isAjax'] == 1) {
			header('Content-Type: application/xml');
		}

		return $c->output;
	}

	function fourohfour() {
		$basePath= $this->constructBasePath();
		if (file_exists(ROOT."views".DS."layouts".DS."404.thtml")) {
			include ROOT."views".DS."layouts".DS."404.thtml";
		} else {
			include LIB."views".DS."layouts".DS."404.thtml";
		}
	}

	/**
	 * extract,clean and dequote any given get/post-parameters
	 * find out which controller and view we should use
	 * @param string $url raw url (see dispatch())
	 */
	private function constructParams($url, $routes= null) {
		if (!empty ($routes) && is_array($routes)) {
			foreach ($routes as $old => $new) {
				if ($old == $url) {
					$url= $new;
					break;
				}
				if (($old != '') && ($old == substr($url, 0, strlen($old)))) {
					$url= $new.substr($url, strlen($old));
					break;
				}
			}
		}

		$paramList= explode('/', $url);

		if (isset ($paramList[0]) && ($paramList[0]) == 'ajax') {
			array_shift($paramList);
			$this->params['isAjax']= 1;
		} else {
			$this->params['isAjax']= 0;
		}

		if (isset ($paramList[0]) && !empty ($paramList[0])) {
			$controller= strtolower(array_shift($paramList));
		} else {
			$controller= "main";
		}

		if (isset ($paramList[0]) && !empty ($paramList[0])) {
			$action= strtolower(array_shift($paramList));
		} else {
			$action= "index";
			if (isset ($paramList[0]))
				unset ($paramList[0]);
		}

		$this->params['pass']= $paramList;

		if (!empty ($_GET)) {
			unset ($_GET['kata']);
			if (ini_get('magic_quotes_gpc') == 1) {
				$this->params['url']= stripslashes_deep($_GET);
			} else {
				$this->params['url']= $_GET;
			}
		}

		if (!empty ($_POST)) {
			if (ini_get('magic_quotes_gpc') == 1) {
				$this->params['form']= stripslashes_deep($_POST);
			} else {
				$this->params['form']= $_POST;
			}
		}

		$this->params['controller']= $controller;
		$this->params['action']= $action;
	}

	/**
	 * construct the url path under which this framework can be called from the browser. adds / at the end
	 * @return string
	 */
	private function constructBasePath() {
		$base= dirname(dirname(env('PHP_SELF')));
		if (substr($base, -1, 1) != '/') {
			$base .= '/';
		}
		return $base;
	}

	/**
	 * tries to construct the base url under which this framework can be called from the browser. adds a "/" at the end
	 */
	private function constructBaseUrl() {
		return 'http'. (env('HTTPS') != '' ? 's' : '').'://'.
		env('SERVER_NAME'). (env('SERVER_PORT') != '80' ? (':'.env('SERVER_PORT')) : '').
		$this->constructBasePath();
	}
} //class