<?php

/**
 * @package kata_component
 */

/**
 * included derived classes depending on storage-method 
 */
if (!defined('SESSION_STORAGE')) {
	require (LIB . 'controllers' . DS . 'components' . DS . 'file.session.php');
} else {
	require (LIB . 'controllers' . DS . 'components' . DS . strtolower(SESSION_STORAGE) . '.session.php');
}

/**
 * base session class
 * 
 * @author feldkamp@gameforge.de
 * @author joachim.eckert@gameforge.de
 * @package kata_component
 */
class baseSessionComponent {
	/**
	 * placeholder for controller that owns this component
	 * @var object
	 */
	public $controller = false;

	/**
	 * array of strings of component-names this component needs to function. null or false if none.
	 * @var array
	 */
	public $components = array ();

	/**
	 * path that we use when we set the cookie
	 * @var string
	 */
	protected $path;

	/**
	 * domain that we use when we set the cookie
	 * @var string
	 */
	protected $domain;

	/**
	 * useragent that we use when we set/check a session-cookie
	 * @var string
	 */
	protected $userAgent = null;

	/**
	 * time that we use when we check a session cookie
	 * @var int
	 */
	protected $time = null;

	/**
	 * time after that the session expires (normally time+SESSION_TIMEOUT, as set in config/core.php)
	 * @var int
	 */
	protected $sessionTime = 0;

	/**
	 * perform needed initialization and cache the controller that called us
	 */
	public function startup(& $controller) {
		if (!defined('SESSION_UNSAFE')) {
			define('SESSION_UNSAFE', false);
		}

		$this->controller = $controller;
		if (CLI) {
			return;
		}
	}

	function constructParams() {
		//already constructed?
		if (null !== $this->time) {
			return;
		}

		if (!defined('SESSION_BASEDOMAIN') || (!SESSION_BASEDOMAIN)) {
			$this->domain = env('SERVER_NAME');
		} else {
			$parts = explode('.', env('SERVER_NAME'));
			while (count($parts) > 2) {
				array_shift($parts);
			}
			$this->domain = '.' . implode('.', $parts);
		}

		if (empty ($this->controller->basePath)) {
			$this->path = '/';
		} else {
			$this->path = $this->controller->basePath;
		}

		$this->time = time();
		if (SESSION_TIMEOUT > 0) {
			$this->sessionTime = $this->time + SESSION_TIMEOUT;
		} else {
			$this->sessionTime = 0;
		}

		if (env('HTTP_USER_AGENT') != null) {
			$this->userAgent = md5(env('HTTP_USER_AGENT') . (!SESSION_UNSAFE ? $this->getIp() : '') . SESSION_STRING);
		} else {
			$this->userAgent = md5((!SESSION_UNSAFE ? $this->getIp() : '') . SESSION_STRING);
		}
	}

	/**
	 * did we already initialize the session?
	 * @var boolean
	 */
	private $didInitSession = false;

	/**
		 * setting some ini-parameters and starting the actual session. is done lazy (only when needed)
		 * @param $forRead boolean if true we dont initialize the session if no sessioncookie exists
		 */
	protected function initSession($forRead) {
		if ($this->didInitSession) {
			return true;
		}

		if ($forRead) {
			if (!isset ($_COOKIE[SESSION_COOKIE]) || empty ($_COOKIE[SESSION_COOKIE])) {
				return false;
			}
		}

		$this->constructParams();
		$this->startupSession();
		$this->didInitSession = true;
		$this->checkValid();
		return true;
	}

	protected function initSessionParams() {
		ini_set('url_rewriter.tags', '');
		ini_set('session.use_cookies', 1);
		ini_set('session.name', SESSION_COOKIE);
		ini_set('session.cookie_lifetime', SESSION_TIMEOUT);
		ini_set('session.gc_maxlifetime', SESSION_TIMEOUT + 1);
		ini_set('session.cookie_path', $this->path);
		ini_set('session.cookie_domain', $this->domain);
	}

	protected function initCookie() {
		session_cache_limiter("must-revalidate");
		header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
	}

	protected function clearCookie() {
		setcookie(SESSION_COOKIE, '', time() - DAY, $this->path, $this->domain);
	}

	/**
	 * check if the session expired, or something suspicious happend
	 */
	protected function checkValid() {
		if (!is_null($this->read('SessionConfig'))) {
			if ($this->userAgent == $this->read('SessionConfig.userAgent') && (($this->read('SessionConfig.time') == 0) || ($this->time <= $this->read('SessionConfig.time')))) {
				$this->write('SessionConfig.time', $this->sessionTime);
			} else {
				// session hijacking
				$this->destroy();
			}
		} else {
			srand((double) microtime() * 1000000);
			$this->write('SessionConfig', 1);
			$this->write('SessionConfig.userAgent', $this->userAgent);
			$this->write('SessionConfig.time', $this->sessionTime);
			$this->write('SessionConfig.rand', rand());
		}
	}

	/**
	 * checks if you used a valid string  as identifier
	 * @param string $name may contain a-z, A-Z, 0-9, ._-
	 */
	protected function validateKeyName($name) {
		if (is_string($name) && preg_match("/^[0-9a-zA-Z._-]+$/", $name)) {
			return;
		}
		throw new InvalidArgumentException("'$name' is not a valid session string identifier");
	}

	/**
	 * check obvious conditions for all operations
	 * 
	 * @param string $name name under which the value(s) are to find
	 * @param bool $forRead if we initialize for write (read: if we need to create a session if non-existing)
	 * @return bool success
	 */
	protected function preamble($name = null, $forRead = true) {
		if (CLI) {
			return false;
		}
		if (empty ($name)) {
			return false;
		}
		$this->validateKeyName($name);
		return $this->initSession($forRead);
	}

	/**
	 * try to do an educated guess about the users real ip, even if he is behind proxies
	 * 
	 * @return string ip or '0.0.0.0' if failure
	 */
	public function getIp() {
		foreach (array (
				'HTTP_CLIENT_IP',
				'HTTP_X_FORWARDED_FOR',
				'HTTP_X_FORWARDED',
				'HTTP_FORWARDED_FOR',
				'HTTP_FORWARDED',
				'HTTP_X_COMING_FROM',
				'HTTP_COMING_FROM',
				'REMOTE_ADDR'
			) as $envvar) {
			$ip = env($envvar);
			if (!empty ($ip)) {
				return $ip;
			}
		}
		return '0.0.0.0';
	}

}
