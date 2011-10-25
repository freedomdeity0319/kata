<?php
    require_once('simpletest/unit_tester.php');
    require_once('simpletest/reporter.php');
    require_once('ControllerTest.php');
    require_once('ControllerRedirektTest.php');
    require_once('Model2Test.php');
    require_once('DboTest.php');

    $test = &new TestSuite('kataTestSuite');

    $test->addTestCase(new Model2Test("mysql"));
    $test->addTestCase(new Model2Test("mssql"));

//    $test->addTestCase(new DboTest("mssql"));
//    $test->addTestCase(new DboTest("mysql"));

//    $test->addTestCase(new ControllerTest());
//    $test->addTestCase(new ControllerRedirektTest());
    $test->run(new MyHTMLReporter(1));
