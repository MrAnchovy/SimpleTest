<?php
/**
 * This file launches the Sins browser-based test framework.
 *
 * It is supplied with settings that should work "out of the box", but you will
 * want to change these - see the documentation for more information.
 *
 * @package    Sins
 * @version    2.0.0-dev
 * @link       https://github.org/MrAnchovy/Sins
 * @copyright  Copyright © 2013 [MrAnchovy](http://www.mranchovy.com/).
 * @license    [MIT](http://opensource.org/licenses/MIT)
**/

// the class file must already have been included
$local = new \Sins\Local;

// set the start time for logging
if (isset($startTime)) {
    $local->startTime = $startTime;
    unset($startTime);
} else {
    $local->startTime = microtime(true);
}

// set the directory containing Sins
$local->rootPath = __DIR__;

if (!class_exists('\Sins\Core')) {
    // we have no autoloader, so get one
    require __DIR__ . '/classes/Sins/Core.php';
}


// just an example follows

$useAutorun = false;

if ($useAutorun) {

    // autorun uses a shutdown handler which may not play nicely with other things
    require_once __DIR__ . '/simpletest/autorun.php';

} else {

    // this doesn't seem to include everything required
//    require_once(__DIR__ . '/simpletest/simpletest.php');
    require_once(__DIR__ . '/simpletest/unit_tester.php');
//    require_once(__DIR__ . '/simpletest/mock_objects.php');
//    require_once(__DIR__ . '/simpletest/collector.php');
//    require_once(__DIR__ . '/simpletest/default_reporter.php');

}

// require_once(__DIR__ . '/simpletest/test/all_tests.php');
//        $this->addFile(dirname(__FILE__) . '/unit_tests.php');
//        $this->addFile(dirname(__FILE__) . '/shell_test.php');
//        $this->addFile(dirname(__FILE__) . '/live_test.php');
//        $this->addFile(dirname(__FILE__) . '/acceptance_test.php');

class Example_TestSuite extends TestSuite
{
    function Example_TestSuite() {
        // REVISIT why do we need this?
        parent::__construct();
        $this->TestSuite('Show Sins is working - Sins version ' . \Sins\Core::VERSION);
        $this->addFile('test/example/test_Demonstration_of_SimpleTest_tests.php');
    }
}

if ($useAutorun) {

    // it runs tests in a shutdown handler so all we have to do is exit
    return;

} else {

    $testSuite = new Example_TestSuite;
    $testSuite->run(new HtmlReporter);

}
