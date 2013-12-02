<?php

class Example_TestSuite extends TestSuite
{
    function Example_TestSuite() {
        // REVISIT why do we need this?
        parent::__construct();
        $this->TestSuite('Show Sins is working - Sins version ' . \Sins\Core::VERSION);
        $this->addFile('test/example/test_Demonstration_of_SimpleTest_tests.php');
        $this->addFile('test/example/test_Demonstrate_expectation_tests.php');
    }
}
