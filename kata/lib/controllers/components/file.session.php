<?php
/**
 * @package kata_component
 */



/**
 * A component for object oriented session handling using filesystem or memcached
 *
 * @author mnt@codeninka.de
 * @package kata_component
 */
class SessionComponent {
	/**
	 * placeholder for controller that owns this component
	 * @var object
	 */
	public $controller= false;

	/**
	 * array of strings of component-names this component needs to function. null or false if none.
	 * @var array
	 */
	public $components= array ();

	/**
	 * path that we use when we set the cookie
	 * @var string
	 */
	private $path;
	/**
	 * useragent that we use when we set/check a session-cookie
	 * @var string
	 */
	private $userAgent= null;

	/**
	 * time that we use when we check a session cookie
	 * @var int
	 */
	private $time= 0;

	/**
	 * time after that the session expires (normally time+SESSION_TIMEOUT, as set in config/core.php)
	 * @var int
	 */
	private $sessionTime= 0;

	/**
	 * perform needed initialization and cache the controller that called us
	 */
	public function startup(& $controller) {
		$this->controller= $controller;
		if (CLI) {
			return;
		}
	}

	/**
	 * did we already initialize the session?
	 * @var boolean
	 */
	private $didInitialize= false;

	/**
	 * setting some ini-parameters and starting the actual session. is done lazy (only when needed)
	 * @param $forRead boolean if true we dont initialize the session if no sessioncookie exists
	 */
	private function initSession($forRead) {
		if ($this->didInitialize) {
			return true;
		}
		if ($forRead) {
			if (!isset ($_COOKIE[SESSION_COOKIE]) || empty ($_COOKIE[SESSION_COOKIE])) {
				return false;
			}
		}

		if (empty ($this->controller->basePath)) {
			$this->path= '/';
		} else {
			$this->path= $this->controller->basePath;
		}

		$this->time= time();
		if (SESSION_TIMEOUT > 0) {
			$this->sessionTime= $this->time + SESSION_TIMEOUT;
		} else {
			$this->sessionTime= 0;
		}

		if (env('HTTP_USER_AGENT') != null) {
			$this->userAgent= md5(env('HTTP_USER_AGENT').$this->getIp().SESSION_STRING);
		} else {
			$this->userAgent= md5($this->getIp().SESSION_STRING);
		}

		try {
			session_write_close();
		} catch (Exception $e) {
			session_destroy();
		}

		$this->initCookie();
		//silent because sometimes our cleanup collides with debians cleanup-cronjob
		@session_start();
		$this->didInitialize= true;
		$this->checkValid();
		return true;
	}

	protected function initCookie() {
		//kataMakeTmpPath('sessions');
		ini_set('session.use_trans_sid', 0);
		ini_set('url_rewriter.tags', '');
		ini_set('session.serialize_handler', 'php');
		ini_set('session.use_cookies', 1);
		ini_set('session.name', SESSION_COOKIE);
		ini_set('session.cookie_lifetime', SESSION_TIMEOUT);
		ini_set('session.gc_maxlifetime', SESSION_TIMEOUT + 1);
		ini_set('session.cookie_path', $this->path);
		ini_set('session.gc_probability', 1);
		ini_set('session.auto_start', 0);
		//ini_set('session.save_path', KATATMP.'sessions');

		session_cache_limiter("must-revalidate");
		header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
	}

	/**
	 * check if the session expired, or something suspicious happend
	 */
	private function checkValid() {
		if (!is_null($this->read('Config'))) {
			if ($this->userAgent == $this->read('Config.userAgent') && (($this->read('Config.time')==0)||($this->time <= $this->read('Config.time')))) {
				$this->write('Config.time', $this->sessionTime);
			} else {
				$this->destroy();
				throw new Exception('Session Highjacking detected');
			}
		} else {
			srand ((double)microtime() * 1000000);
			$this->write('Config',1);
			$this->write('Config.userAgent', $this->userAgent);
			$this->write('Config.time', $this->sessionTime);
			$this->write('Config.rand', rand());
		}
	}

	/**
	 * read value(s) from the session container.
	 * returns all currently set values if called with null
	 * returns null when nothing could be found under the name you gave
	 * @param string $name name under which the value(s) are to find
	 */
	public function read($name= null) {
		if (CLI) {
			return null;
		}
		if (!$this->initSession(true)) {
			return null;
		}
		if (empty ($name)) {
			return null;
		}

		if (isset ($_SESSION[$name]) && !is_null($_SESSION[$name])) {
			return $_SESSION[$name];
		}
		return null;
	}

	/**
	 * write mixed values to the session-component.
	 * @param string $name identifier, may contain alphanumeric characters or .-_
	 * @param mixed $value values to store
	 */
	public function write($name, $value) {
		if (CLI) {
			return false;
		}

		$this->initSession(false);

		$var= $this->validateKeyName($name);

		if (empty ($var)) {
			return false;
		}
		unset ($_SESSION[$name]);
		$_SESSION[$name]= $value;
	}

	/**
	 * delete values stored under given name from the session-container
	 * @param string $name identifier
	 */
	public function delete($name) {
		if (CLI) {
			return false;
		}

		$this->initSession(false);

		if (!empty ($name)) {
			unset ($_SESSION[$name]);
		}
	}

	/**
	 * checks if you used a valid string  as identifier
	 * @param string $name may contain a-z, A-Z, 0-9, ._-
	 */
	private function validateKeyName($name) {
		if (is_string($name) && preg_match("/^[0-9a-zA-Z._-]+$/", $name)) {
			return $name;
		}
		throw new InvalidArgumentException("'$name' is not a valid session string identifier");
		return false;
	}

	/**
	 * try to do an educated guess about the users real ip, even if he is behind proxies
	 */
	public function getIp() {
		$it= getUtil('Ip');
		return $it->getIp();
	}

	/**
	 * destroy any current session and all variables stored in the session-container with it
	 */
	public function destroy() {
		$this->initSession(true);
		$sid = session_id();
		if (!empty($sid)) {
			session_destroy();
			setcookie(SESSION_COOKIE, "", time() - DAY, $this->path);
		}
	}
}
