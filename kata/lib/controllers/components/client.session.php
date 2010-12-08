<?php
/**
 * @package kata
 */





/**
 * A component for object oriented session handling using client-side cookies. dont forget: cookies are sent on any request!
 * @author mnt@codeninka.de
 * @package kata
 * @ignore
 */
class SessionComponent
{
	/**
	 * placeholder for controller that owns this component
	 * @var object
	 */
	public $controller = false;

	/**
	 * array of strings of component-names this component needs to function. null or false if none.
	 * @var array
	 */
	public $components = array();

	/**
	 * path that we use when we set the cookie
	 * @var string
	 */

	private $path;
	/**
	 * useragent that we use when we set/check a session-cookie
	 * @var string
	 */
	private $userAgent=null;

	/**
	 * time that we use when we check a session cookie
	 * @var int
	 */
	private $time=0;

	/**
	 * time after that the session expires (normally time+SESSION_TIMEOUT, as set in config/core.php)
	 * @var int
	 */
	private $sessionTime=0;

	/**
	 * perform needed initialization and cache the controller that called us
	 */
	public function startup(&$controller)
	{
		$this->controller=$controller;

		if (SESSION_START) {

			if (empty($this->controller->basePath)) {
				$this->path = '/';
			} else {
				$this->path = $this->controller->basePath;
			}

			$this->time=time();
			if (SESSION_TIMEOUT>0) {
				$this->sessionTime=$this->time+SESSION_TIMEOUT;
			} else {
				$this->sessionTime=0;
			}

			if (env('HTTP_USER_AGENT') != null) {
				$this->userAgent = md5(env('HTTP_USER_AGENT') . $this->getIp(). SESSION_STRING);
			} else {
				$this->userAgent = md5($this->getIp().SESSION_STRING);
			}

			try {
				$this->initSession();
				$this->checkValid();
			} catch (Exception $e) {
				setcookie(SESSION_COOKIE,false);
				$this->initSession();
				$this->checkvalid();
			}
		}
	}

	/**
	 * default constructor. initializes some variables needed to construct a session
	 * and tries to start/continue a session, throws exception if that fails
	 */
	function __construct () {
	}

	/**
	 * setting some ini-parameters and starting the actual session
	 */
	private function initSession() {
		header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
	}

	/**
	 * check if the session expired, or something suspicious happend
	 */
	private function checkValid() {
		if ($this->read('Config')) {
			if ($this->userAgent == $this->read('Config.userAgent') && (($this->read('Config.time')==0)||($this->time <= $this->read('Config.time')))) {
				$this->write('Config.time', $this->sessionTime);
			} else {
				throw new Exception('Session Highjacking detected');
			}
		} else {
			srand ((double)microtime() * 1000000);
			$this->writeArray(array(
                        'Config' => 1,
                        'Config.userAgent' => $this->userAgent,
                        'Config.time' => $this->sessionTime,
                        'Config.rand' => rand()
			));
		}
	}

	private function calculateHash() {
		return md5(serialize($this->sessionData).SESSION_STRING);
	}

	private $sessionData=array();
	private $isSessionDecoded=false;
	private function decodeSession() {
		if ($this->isSessionDecoded) {
			return;
		}

		$s = is($_COOKIE[SESSION_COOKIE],'');
		if ($s != '') {
			$data = base64_decode(urldecode($s));
			if (false === $data) {
				throw new Exception('Session: cant base64decode data');
			}

			$data = gzinflate($data);
			if (false === $data) {
				throw new Exception('Session: cant uncompress data');
			}

			$this->sessionData = unserialize($data);
			if (false === $this->sessionData) {
				throw new Exception('Session: cant unserialize data');
			}

			$hash = is($this->sessionData['Config.hash'],'');
			unset($this->sessionData['Config.hash']);
			if ($hash != $this->calculateHash()) {
				throw new Exception('Session: hash mismatch (user tampering?)');
			}
		}
		$this->isSessionDecoded = true;
	}

	private function encodeSession() {
		$this->sessionData['Config.hash'] = $this->calculateHash();
		$data = urlencode(base64_encode(gzdeflate(serialize($this->sessionData))));
		unset($this->sessionData['Config.hash']);
		setcookie(SESSION_COOKIE,$data,time()+SESSION_TIMEOUT,'/');
	}

	/**
	 * read value(s) from the session container.
	 * returns all currently set values if called with null
	 * returns null when nothing could be found under the name you gave
	 * @param string $name name under which the value(s) are to find
	 */
	public function read($name = null) {
		$this->decodeSession();

		if (is_null($name)) {
			return $this->sessionData;
		}

		if (empty($name)) {
			return false;
		}

		if (isset($this->sessionData[$name]) && !is_null($this->sessionData[$name])) {
			return $this->sessionData[$name];
		}
		return null;
	}

	/**
	 * write mixed values to the session-component.
	 * @param string $name identifier, may contain alphanumeric characters or .-_
	 * @param mixed $value values to store
	 */
	public function write($name, $value) {
		$var = $this->validateKeyName($name);

		if (empty($var)) {
			return false;
		}

		$this->sessionData[$name]=$value;
		$this->encodeSession();
	}

	/**
	 * write multiple key value arrays to session-component
	 * @param $arr array with key-value pairs
	 */
	public function writeArray($arr) {
		foreach ($arr as $k=>$v) {
			$k = $this->validateKeyName($k);
			$this->sessionData[$k]=$v;
		}
		$this->encodeSession();
	}

	/**
	 * delete values stored under given name from the session-container
	 * @param string $name identifier
	 */
	public function delete($name) {
		if (!empty($name)) {
			unset($this->sessionData[$name]);
		}
		$this->encodeSession();
	}

	/**
	 * checks if you used a valid string  as identifier
	 * @param string $name may contain a-z, A-Z, 0-9, ._-
	 */
	private function validateKeyName($name) {
		if (is_string($name) && preg_match("/^[0-9a-zA-Z._-]+$/", $name)) {
			return $name;
		}
		throw new exception("'$name' is not a valid session string identifier");
		return false;
	}

	/**
	 * try to do an educated guess about the users real ip, even if user is behind proxies
	 */
	public function getIp() {
		$it = getUtil('Ip');
		return $it->getIp();
	}

	/**
	 * destroy any current session and all variables stored in the session-container with it
	 */
	public function destroy() {
		session_destroy();
	}
}


