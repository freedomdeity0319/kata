<?php
/**
 * adapter-class using simpletest-interface so we end up with the same test-interface 
 *
 * @package katatest_lib
 * @author mnt@codeninja.de
 */


/**
 * simpletest MUST be in your include-path, so we can find eg. 'unit_test.php'
 */
require_once 'unit_tester.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'simpleTestExtension.php';

SimpleTest::prefer(new MyHTMLReporter(1));

require_once 'autorun.php';

/**
 * @package katatest_lib
 * @author Dietmar Riess
 */
class PHPUnit_Framework_TestCase extends UnitTestCase implements SimpleTestExtensionInterface {
	public $extension = null;

	function __construct (){
		$this->extension = new SimpleTestExtension($this);
	}
	/**
	 * @see SimpleTestExtension
	 */
	function after($method){
		parent::after($method);
		$this->extension->after($method);
	}
	/**
	 * @see SimpleTestExtension
	 */
	function createTmpFile($name,$content){
		$this->extension->createTmpFile($name,$content);
	}
	/**
	 * @see SimpleTestExtension
	 */
	function assertContain($needle,$haystack,$message=null){
		$this->extension->assertContain($needle,$haystack,$message);
	}
	/**
	 * @see SimpleTestExtension
	 */
	function assertNotContain($needle,$haystack,$message=null){
		$this->extension->assertNotContain($needle,$haystack,$message);
	}
	/**
	 * @see SimpleTestExtension
	 */
	function replaceTmpFile($name,$content,$replace){
		$this->extension->replaceTmpFile($name,$content,$replace);
	}
	function sendMessage($msg){
		$message = $this->extension->sendMessage($msg);
		parent::sendMessage($message);
	}
}
require_once ('kataTestBase.class.php');
