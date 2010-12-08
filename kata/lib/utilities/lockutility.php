<?php
/**
 * @package kata
 */




/**
 * systemwide locking mechanism with timeout for critical sections or eventhandlers
 *
 * @package kata_utility
 * @author mnt@codeninja.de
 */
class LockUtility {
	/**
	 * @var integer seconds to wait until we timeout
	 */
	private $timeout= 10;

	/**
	 * @var array holds lock-status
	 */
	private $locks= array ();

	/**
	 * @param integer $timout how many seconds to wait for a lock before we fail
	 */
	public function setTimeout($timeout) {
		if (is_numeric($timeout) && ($timeout > 0)) {
			$this->timeout= $timeout;
			return true;
		}
		return false;
	}

	/**
	 * Lock a user id
	 *
	 * @param int $id, id of the user to lock.
	 * @param bool $waitForTimeout, wait for time out
	 * @return bool, returns true if the user was locked
	 */
	function lock($id, $waitForTimeout= true) {
		if (substr(PHP_OS, 0, 3) == 'WIN') {
			return true;
		}

		$timeout= time() + $this->timeout;
		$lockname= KATATMP.'sessions'.DS.'lockfile'.urlencode($id);
		$fplock= null;

		kataMakeTmpPath('sessions');

		while ((time() < $timeout) && $waitForTimeout) {
			if ($fplock= fopen($lockname, "w+")) {
				if (flock($fplock, LOCK_EX | LOCK_NB)) {
					break;
				}
				if ($fplock) {
					fclose($fplock);
					$fplock= null;
				}
			}
			usleep(100000);
		}

		if ($fplock) {
			$this->locks[$id]= $fplock;
			return true;
		}

		return false;
	}

	/**
	 * Unlock a user id
	 *
	 * @param int $id, id of the user to lock
	 * @return true if the user was unlocked
	 */
	function unlock($id) {
		if (substr(PHP_OS, 0, 3) == 'WIN') {
			return true;
		}

		if (!isset($this->locks[$id])) {
			if (DEBUG > 0) {
				throw new Exception("user $userid not locked");
			}
			return false;
		}

		$fplock= $this->locks[$id];
		flock($fplock, LOCK_UN);
		fclose($fplock);

		@ unlink(KATATMP.'sessions'.DS.'lockfile'.urlencode($id));
		unset ($this->locks[$id]);
		return true;
	}

	function __destruct() {
		if (count($this->locks) > 0) {
			if (DEBUG > 0) {
				throw new Exception("these locks have not been unlocked:".print_r($this->locks, true));
			}
		}
	}

}
