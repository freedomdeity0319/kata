<?php
/**
 * adapter-class so we end up with the same test-interface
 *
 * @package katatest_lib
 * @author Dietmar Riess
 */


/**
 * simpletest MUST be in your include-path, so we can find eg. 'unit_test.php'
 */
require_once 'web_tester.php';

SimpleTest::prefer(new TextReporter());

require_once 'autorun.php';

/**
 * webtest-adapter-class 
 * 
 * @package katatest_lib
 * @author Dietmar Riess
 */
class PHPUnit_Framework_TestCase extends WebTestCase {
	public function orginalGet($url, $parameters = false){
		parent::get($url, $parameters = false);
	}
}

require_once 'kataTestBase.class.php';
