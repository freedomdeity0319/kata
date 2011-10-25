<?php
require dirname(__FILE__).DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."simpletest".DIRECTORY_SEPARATOR."web_tester.php";
require_once('simpletest'.DIRECTORY_SEPARATOR.'autorun.php');
SimpleTest::prefer(new TextReporter());

class PHPUnit_Framework_TestCase extends WebTestCase {

}

require_once ('kataTestBase.class.php');
