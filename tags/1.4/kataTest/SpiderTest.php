<?php
/**
 * @package katatest_case
 */


/**
 * requirements
 */
require ('lib'.DIRECTORY_SEPARATOR.'kataTestBase.phpUnit.class.php');
require ('lib'.DIRECTORY_SEPARATOR.'SpiderBase.class.php');


/**
 * an example on how to use the spider-base-class
 *
 * DONT FORGET: set DEBUG=1 in core.php or php wont generate any errors this test is able to see
 *
 * @author mnt@codeninja.de
 * @package katatest_case
 */
class SpiderTest extends SpiderBaseTest {

	function setUp() {
		$this->clearSession();
	}

	/**
	 * if you return false this url wont be visited
	 */
	function urlCallback($url) {
		return (strpos($url, 'user/search') === false) && (strpos($url, 'user/logout') === false);
	}

/**
 * this routine injects values that may possibly break the webapp
 */
	function valueCallback($url, $name, & $value) {
		if (is_numeric($value)) {
			if ($value > 0) {
				$value= -999999;
			} else {
				$value= 999998;
			}
			return true;
		}

		$value= "<foo>'";
		return true;
	}

	function testLoggedIn() {
		$this->login(USERNAME, PASSWORD);
		$this->weMustLandAtURL('mymmo/index');
		$this->mustHaveLink('user/logout');

		$this->visit('main/index', 'START');
	} //func

	function testLoggedOut() {
		$this->visit('main/index', 'START');
	} //func

}
