<?php
/**
 * Controller for running tests.
 *
 * @package    Sins
 * @copyright  Copyright © 2013 [MrAnchovy](http://www.mranchovy.com/).
 * @license    [MIT](http://opensource.org/licenses/MIT)
**/

namespace Sins\Controller;

class Controller_run extends \Sins\Controller {

    function executeApiAction_autorun($id = null) {
        $this->autorun(__DIR__ . '/../../../simpletest/test/dumper_test.php');
    }

    function autorun($files) {
        $autorun = new \Sins\Autorun;
        $reporter = new \Sins\Reporter\JsonReporter;
        $reporter->outputArray = true;
        $autorun->reporter = $reporter;
        $autorun->start();
        if (is_array($files)) {
            foreach ($files as $file) {
                include $file;
            }
        } else {
            include $files;
        }
        $autorun->finish();
        return $reporter->outputArray;
    }

    function executeApi($id = null) {

        $this->response->body = array(
            'status' => 'ok',
            'testResult' => $this->autorun(array(
                __DIR__ . '/../../../simpletest/test/dumper_test.php',
                __DIR__ . '/../../../test/example/example-testsuite.php',
            )),
        );
        return;


        //    require_once(__DIR__ . '/simpletest/simpletest.php');
            require_once($this->local->baseDir.'simpletest/unit_tester.php');
        //    require_once(__DIR__ . '/simpletest/mock_objects.php');
        //    require_once(__DIR__ . '/simpletest/collector.php');
        //    require_once(__DIR__ . '/simpletest/default_reporter.php');

        // require_once(__DIR__ . '/simpletest/test/all_tests.php');
        //        $this->addFile(dirname(__FILE__) . '/unit_tests.php');
        //        $this->addFile(dirname(__FILE__) . '/shell_test.php');
        //        $this->addFile(dirname(__FILE__) . '/live_test.php');
        //        $this->addFile(dirname(__FILE__) . '/acceptance_test.php');

        include __DIR__ . '/../../../test/example/example-testsuite.php';

        $testSuite = new \Example_TestSuite;
        $reporter = new \Sins\Reporter\JsonReporter;
        $reporter->outputArray = true;
        $testSuite->run($reporter);

        $this->response->body = array(
            'status' => 'ok',
            'testResult' => $reporter->outputArray,
        );
    }
}
