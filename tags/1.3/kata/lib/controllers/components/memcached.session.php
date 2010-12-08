<?php

/**
 * sessions via memcache
 * 
 * @package kata_component
 */

/**
 * A component for object oriented session handling using memcached
 * needs PECL memcached-extension 2.1.2 or bigger
 *
 * @author feldkamp@gameforge.de
 * @package kata_component
 */
class SessionComponent extends baseSessionComponent {

	/**
	 * setting some ini-parameters and starting the actual session
	 */
	protected function startupSession() {
		$this->initCookie();
		$this->initSessionParams();

		ini_set('memcache.allow_failover', true);
		ini_set('memcache.hash_strategy', 'consistent');
		ini_set('memcache.hash_function', 'fnv');

		// only 2.1.2 has a integrated session handler
		if (version_compare(phpversion('memcache'), '2.1.2', '<')) {
			throw new Exception('You need at least PECL memcached 2.1.2 for session support');
		}

		$servers = explode(',', MEMCACHED_SERVERS);
		$path = '';
		foreach ($servers as $server) {
			$temp = explode(':', $server);
			$path .= 'tcp://' . $temp[0] . ':' . (empty ($temp[1]) ? 11211 : $temp[1]) . ',';
		}
		ini_set('session.save_handler', 'memcache');
		ini_set('session.save_path', substr($path, 0, -1));

		@ session_start();
	}

	/**
	 * read value(s) from the session container.
	 * returns all currently set values if called with null
	 * returns null when nothing could be found under the name you gave
	 * @param string $name name under which the value(s) are to find
	 */
	public function read($name = null) {
		if (CLI) {
			return false;
		}
		if ($this->initSession(true)) {
			if (empty ($name)) {
				return $_SESSION;
			}
			if (isset ($_SESSION[$name])) {
				return $_SESSION[$name];
			}
		}
		return null;
	}

	/**
	 * write mixed values to the session-component.
	 * @param string $name identifier, may contain alphanumeric characters or .-_
	 * @param mixed $value values to store
	 */
	public function write($name, $value) {
		if (parent :: preamble($name, false)) {
			unset ($_SESSION[$name]);
			$_SESSION[$name] = $value;
			return true;
		}
		return false;
	}

	/**
	 * delete values stored under given name from the session-container
	 * @param string $name identifier
	 */
	public function delete($name) {
		if (parent :: preamble($name, false)) {
			unset ($_SESSION[$name]);
			return true;
		}
		return false;
	}

	/**
	 * destroy any current session and all variables stored in the session-container with it
	 */
	public function destroy() {
		session_destroy();
		$_SESSION = null;
		$this->clearCookie();
	}
}