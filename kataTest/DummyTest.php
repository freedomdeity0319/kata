<?php
/**
 * @package kataTestCase
 */



/**
 * include required files (really? :O)
 */
require('lib'.DIRECTORY_SEPARATOR.'kataTestBase.class.php');
require('myvars.php');


/**
 * a very stupid testcase
 * @author mnt@codeninja.de
 * @package kataTestCase
 */
class DummyTest extends kataTestBaseClass {

	function testOne() {
        $this->get('mymmo/index');
        $this->weMustLandAtURL('user/register/msg');
        $this->mustNotHaveLink('user/logout');

        $this->login(USERNAME,PASSWORD);
        $this->mustHaveLink('user/logout');

        $this->get('games/index');
        $this->get('games/gamelist');
        $this->get('games/gamedetails/4story');
        $this->get('games/gamedetails/airrivals');
        $this->get('games/gamedetails/battleknight');
        $this->get('games/gamedetails/bitefight');
        $this->get('games/gamedetails/darkpirates');
        $this->get('games/gamedetails/gladiatus');
        $this->get('games/gamedetails/ikariam');
        $this->get('games/gamedetails/legend');
        $this->get('games/gamedetails/metin2');
        $this->get('games/gamedetails/nostale');
        $this->get('games/gamedetails/mogame');
        $this->get('games/gamedetails/warpfire');

        $this->get('user/logout');
        $this->mustHaveLink('user/register');
	}

}
?>
