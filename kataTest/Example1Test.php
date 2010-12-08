<?php
/**
 * a very stupid testcase
 * 
 * @package katatest_case
 */



/**
 * include required files (really? :O)
 */
require 'lib'.DIRECTORY_SEPARATOR.'kataTestBase.phpUnit.class.php';


/**
 * @author mnt@codeninja.de
 * @package katatest_case
 */
class Example1Test extends kataTestBaseClass {

	function testOne() {
        $this->get('mymmo/index');
        $this->weMustLandAtURL('user/register/msg');
        $this->mustNotHaveLink('user/logout');

        $this->login(USERNAME,PASSWORD);
        $this->mustHaveLink('user/logout');

        $this->get('games/index');

        $this->get('user/logout');
        $this->mustHaveLink('user/register');
	}

	function testTwo() {
		$this->loadModel('Gamedata');
		$this->Gamedata->initialize();

	}

	function testThree() {
		$this->loadController('Games');
		$this->GamesController->team();
		$html = $this->GamesController->output;
		$this->assertTrue(!empty($html));
	}

	function testFour() {
		$this->bootstrapKata();
		$model = new Model;
		$model->useTable = 'meintable';
		$r = $model->query('SELECT 1');
		$this->assertEquals($r[0][1],'1');
	}

}

