<?php

namespace Sins;

class Autorun {

    protected $initialClasses = array();
    public $reporter;

    function start()
    {
        if (!defined('SIMPLETEST_NO_AUTORUN')) {
            define('SIMPLETEST_NO_AUTORUN', true);
        }
        $this->initialClasses = get_declared_classes();
    }

    /**
     *    run all recent test cases if no test has
     *    so far been run. Uses the DefaultReporter which can have
     *    it's output controlled with SimpleTest::prefer().
     *    @return boolean/null false if there were test failures, true if
     *                         there were no failures, null if tests are
     *                         already running
     */
    function finish() {
        if ($this->reporter === null) {
            $this->reporter = new \DefaultReporter();
        }
        try {
            $candidates = array_diff(get_declared_classes(), $this->initialClasses);
            $loader = new \SimpleFileLoader();
            $suite = $loader->createSuiteFromClasses(
                    'Autorun tests - default title',
                    $loader->selectRunnableTests($candidates));
            return $suite->run($this->reporter);
        } catch (Exception $stack_frame_fix) {
            print $stack_frame_fix->getMessage();
            return false;
        }
    }
}
