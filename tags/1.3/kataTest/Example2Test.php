<?php
/**
 * a second example that completely ignores ->bootstrapKata()
 * 
 * @package katatest_case
 */



/**
 * include required files (really? :O)
 */
require_once 'myvars.php';
require_once BASEPATH.'lib/defines.php';
require_once BASEPATH.'lib/controller.php';
require_once BASEPATH.'lib/controllers/app_controller.php';
require_once 'lib'.DIRECTORY_SEPARATOR.'webTest.class.php';



class Example2Test  extends webTestClass {

	function testRedirekt(){
		$this->createTmpFile("controllers".DS.'mock2_controller.php','<?php //generated by Test ?><?class Mock2Controller extends AppController{function index(){$this->redirect("mock3/index");} function index2(){$this->redirect("mock3/index2");}}?>');
		$this->createTmpFile("controllers".DS.'mock3_controller.php','<?php //generated by Test ?><?class Mock3Controller extends AppController{function index(){}function index2(){$this->set("title","Hallo Welt");$this->render("index");}}?>');
		$this->createTmpFile("views".DS.'mock3'.DS.'index.thtml',"<?php //generated by Test// ?>Redirekted");
		$this->createTmpFile("views".DS.'layouts'.DS.'default.thtml','<?php //generated by Test?><title><?=$title_for_layout;?></title><?= $content_for_layout;?>');
		$this->get(BASEURL."mock2");
		$this->assertLandOnUrl(BASEURL."mock3/index");
		$this->assertText("Redirekt");
		$this->assertTitle("");
		$this->get(BASEURL."mock2/index2");
		$this->assertLandOnUrl(BASEURL."mock3/index2");
		$this->assertText("Redirekt");
		$this->assertTitle("Hallo Welt");
	}

}