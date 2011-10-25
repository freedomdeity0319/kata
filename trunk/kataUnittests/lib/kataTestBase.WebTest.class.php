<?php
require_once dirname(__FILE__)."/../simpletest/web_tester.php";
SimpleTest::prefer(new TextReporter());
require_once dirname(__FILE__).'/../simpletest'.DIRECTORY_SEPARATOR.'autorun.php';
class PHPUnit_Framework_TestCase extends WebTestCase {
	public function orginalGet($url, $parameters = false){
		parent::get($url, $parameters = false);
	}
}
require_once ('kataTestBase.class.php');
