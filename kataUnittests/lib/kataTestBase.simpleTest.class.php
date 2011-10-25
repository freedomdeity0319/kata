<?php
require_once dirname(__FILE__)."/../simpletest/unit_tester.php";
require_once dirname(__FILE__)."/simpleTestExtension.php";
SimpleTest::prefer(new MyHTMLReporter(1));
require_once dirname(__FILE__).'/../simpletest'.DIRECTORY_SEPARATOR.'autorun.php';
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
