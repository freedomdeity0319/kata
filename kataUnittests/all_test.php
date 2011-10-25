<?
require_once('simpletest/autorun.php');

class AllTests extends TestSuite {
    function AllTests() {
        $this->TestSuite('All tests');
        $this->addFile(dirname(__FILE__).'/ModelTest.php');
    }
}
?>
