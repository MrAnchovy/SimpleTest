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

namespace SinsScherzo;

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
$local->baseDir = __DIR__ . DIRECTORY_SEPARATOR;


// try and autoload the core class - if you want to override the core, do it here
if (class_exists('\SinsScherzo\Core')) {
    $core = new Core($local);
} else{
    // we have no autoloader so we will have to load it manually
    require __DIR__ . '/classes/SinsScherzo/Core.php';
    // and register our own autoloader
    $core = new Core($local);
    $core->registerClassAutoloader();
}

$app = $core->bootstrap();

try {

    // Create a request object and populate it from the HTTP request.
    $request = new Request($app);
    $request->parseHttp();

    // create a response object
    $response = new Response($request, $app);

    // create a route and dispatch it
    (new Route($app))->parse($request)->dispatch($response);

    $response->send();

    return;

} catch (Exception $e) {
    // let a Scherzo\Exception fall through    

} catch (\Exception $ee) {
    // turn an ordinary Exception into a Scherzo\Exception
    $e = new Exception($ee->getMessage(), array(), 500, $ee);
}

// throw the Scherzo\Exception
throw $e;


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
        $this->addFile('test/example/test_Demonstrate_expectation_tests.php');
    }
}

if ($useAutorun) {

    // it runs tests in a shutdown handler so all we have to do is exit
    return;

} else {

    $testSuite = new Example_TestSuite;
    $testSuite->run(new \Sins\Reporter\JsonReporter);

}
