<?php
/**
 * adapter-class for phpunit, so we end up with the same test-interface
 *
 * @package katatest_lib
 * @author Dietmar Riess
 */


/**
 * include files. you must have PEAR PHPUnit installed
 */
require_once 'PHPUnit/Framework.php';

/**
 * adapter class
 * @package katatest_lib
 */
class kataTestBaseTestClass extends PHPUnit_Framework_TestCase {
}

require_once 'kataTestBase.class.php';
