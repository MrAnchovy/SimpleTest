<?php
/**
 * Report tests in a json format.
 *
 * @package    Sins
 * @copyright  Copyright © 2013 [MrAnchovy](http://www.mranchovy.com/).
 * @license    [MIT](http://opensource.org/licenses/MIT)
**/

namespace Sins\Reporter;

// class JsonReporter extends \SimpleReporter
class JsonReporter
{

    /**
     * Setting for formatting timestamps - either a date() format string or a
     * DateTimezone constant name.00
    **/
    protected $timeFormat = 'ISO8601';

    /**
     * Title of current file, class and method.
    **/
    protected $current = array();

    /**
     * Count of tests, passes, fails etc. for the group on top of the stack.
    **/
    protected $currentGroupCounts = array();

    /**
     * Array of messages (test results, group header/footer).
    **/
    protected $data = array();

    /**
     * Stack of groups (testcase, file, class, method).
    **/
    protected $groupStack = array();

    /**
     * (micro)timestamp of the last event
    **/
    protected $lastEventTime;

    public function __construct()
    {
        if (defined("DateTime::$this->timeFormat")) {
            $this->timeFormat = constant("DateTime::$this->timeFormat");
        }
    }


// --- REVISIT these legacy methods -------------------------------------------

    /**
     * The legacy test case calls this method to let the reporter modify the
     * invoker. Why?
     *
     * @REVISIT       Rewrite the test runner and delete this method.
     * @param   SimpleInvoker $invoker   Individual test runner.
     * @return  SimpleInvoker           Wrapped test runner.
    **/
    public function createInvoker($invoker)
    {
        return $invoker;
    }

    /**
     * The legacy test case calls this method to get a dumper to create the
     * message it sends to the reporter. Why?
     *
     * @REVISIT               Rewrite the test case and delete this method.
     * @return  SimpleDumper  This functionality is not implemented so just say yes.
    **/
    function getDumper()
    {
        return new \SimpleDumper();
    }

    /**
     * Report the current status of the reporter.
     *
     * This is nonsense - the reporter is just a reporter, the runner should
     * keep track of the test status if it wants to.
     *
     * @REVISIT       Rewrite the test runner and delete this method.
     * @return  true  This functionality is not implemented so just say yes.
    **/
    public function getStatus()
    {
        return true;
    }

    /**
     * The legacy test runner calls this method to tell the reporter that it is
     * a dry run. This functionality is not implemented so it is ignored.
     *
     * @REVISIT       Rewrite the test runner and delete this method.
     * @param   bool  Set or unset the dry run status.
     * @return  void
    **/
    public function makeDry($set = true)
    {
    }

    /**
     * The legacy test runner calls this method to let the reporter veto whether
     * a test should be run. Why?
     *
     * @REVISIT         Rewrite the test runner and delete this method - this is nonsense.
     * @param   string  The name of the class containing the method.
     * @param   string  The name of the method to be invoked.
     * @return  true    This functionality is not implemented so just say yes.
    **/
    public function shouldInvoke($class, $method)
    {
        return true;
    }

// PUBLIC METHODS -------------------------------------------------------------
    
    /**
     * This is called by the runner at the end of each 'case' (i.e. a class)
     *
     * @param   string  The class name.
     * @return  void
    **/
    public function paintCaseEnd($title)
    {
        $this->reportGroupEnd();
    }

    /**
     * This is called by the runner at the start of each 'case' (i.e. a class)
     *
     * @param   string  The class name.
     * @return  void
    **/
    public function paintCaseStart($title)
    {
        $this->current['class'] = $title;
        $this->reportGroupBegin($title, 'class');
    }

    /**
     * This is called by the test runner when there has been an unexpected error
     * (not an exception or an expected error) during test execution.
     *
     * @param   string  The message.
     * @return  void
    **/
    public function paintError($message)
    {
        $this->increment('error');
        $this->reportTest('Error', $message);
    }

    /**
     * This is called by the test runner when there has been an unexpected
     * exception during test execution.
     *
     * @param   Exception  An Exception object.
     * @return  void
    **/
    public function paintException(\Exception $e) {
        $this->increment('exception');
        $message = get_class($e)
            . $e->getMessage()
            . ' in ' . $e->getFile()
            . ' at line ' . $e->getLine();
        $this->reportTest('Exception', $message);
    }

    /**
     * This is called by the test runner when a test has failed.
     *
     * @param    Exception  An Exception object.
     * @returns  void
    **/
    public function paintFail($message)
    {
        $this->increment('fail');
        $this->reportTest('Fail', $message);
    }

    /**
     * This is called by the runner at the start of the testsuite and at the start
     * of each file.
     *
     * @param   string  The testsuite or file name.
     * @return  void
    **/
    public function paintGroupEnd($title)
    {
        $this->reportGroupEnd();

        // if the stack is now empty we have reached the end
        if (count($this->groupStack) === 0) {
            $this->reportEnd();
        }
    }

    /**
     * This is called by the runner at the start of the testsuite and at the start
     * of each file.
     *
     * @param   string  The testsuite or file name.
     * @return  void
    **/
    public function paintGroupStart($title, $size = null)
    {
        // work out what sort of group it is
        switch (count($this->groupStack)) {

            case 0 :
                // if the stack is empty it must be the start
                $this->reportBegin($title, $size);
                $this->reportGroupBegin($title, 'testsuite');
                break;

            case 1 :
                // this is a new file
                $this->reportGroupBegin($title, 'file');
                $this->current['file'] = $title;
                break;

            default:
                $this->reportGroupBegin($title, 'unknown');
        }
    }

    /**
     * This is called by the runner at the start of each method.
     *
     * @param   string  The method name.
     * @return  void
    **/
    public function paintMethodStart($title)
    {
        $this->current['method'] = $title;
        $this->reportGroupBegin($title, 'method');
    }

    /**
     * This is called by the runner at the end of each method.
     *
     * @param   string  The method name.
     * @return  void
    **/
    public function paintMethodEnd($title)
    {
        $this->reportGroupEnd();
    }

    /**
     * This is called by the test runner when a test has passed.
     *
     * @param    Exception  An Exception object.
     * @returns  void
    **/
    public function paintPass($message)
    {
        $this->increment('pass');
        $this->reportTest('Pass', $message);
    }

    /**
     * This is called by the test runner when a test has been skipped.
     *
     * @param    Exception  An Exception object.
     * @returns  void
    **/
    public function paintSkip($message) {
        $this->increment('skip');
        $this->reportTest('Skip', $message);
    }

// PRIVATE METHODS -------------------------------------------------------------

    /**
     * Increment a counter for the current group.
     *
     * @param   string  The counter name.
     * @return  void
    **/
    protected function increment($counter)
    {
        $this->currentGroupCounts['tests']++;
        if (isset($this->currentGroupCounts[$counter])) {
            $this->currentGroupCounts[$counter]++;
        } else {
            $this->currentGroupCounts[$counter] = 1;
        }
    }

    /**
     * Paint the header - this is not part of the interface with the runner.
     *
     * @param   string  The title of the test suite.
     * @return  void
    **/
    protected function reportBegin($name, $size) {
        $message = array(
            'type' => 'ReportStart',
            'startTime' => date($this->timeFormat),
            'reporter' => __CLASS__,
            'version' => 'Sins ' . \Sins\Core::VERSION,
        );
        $this->reportEvent($message);
    }

    /**
     * Paint the footer - called at the end of the test suite.
     *
     * @TODO            Use $response instead of sending header directly.
     * @param   string  The title of the test suite.
     * @return  void
    **/
    protected function reportEnd()
    {
        $message = array(
            'type' => 'ReportEnd',
            'endTime' => date($this->timeFormat),
        );
        $this->reportEvent($message);

        // build the message
        $message = array(
            'status' => 'ok',
            'events' => $this->data,
        );

        // send it
        if (true) {
            header('Content-Type: application/json');
            echo json_encode($this->data);
        } else {
            header('Content-Type: text/plain');
            echo json_encode($message, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Add an event to the report.
     *
     * @param    array  The event.
     * @returns  void
    **/
    protected function reportEvent($event)
    {
        $this->lastEventTime = microtime(true);
        $this->data[] = $event;
    }

    /**
     * Start a group for collating and counting test results.
     *
     * @param   string  Group title.
     * @param   string  Group type (testsuite, class, method).
     * @return  void
    **/
    protected function reportGroupBegin($title, $type)
    {
        // set up the event details
        $group = array(
            'groupType' => $type,
            'title' => $title,
            'time' => microtime(true),
        );

        // put this on the stack
        array_push($this->groupStack, $group);

        // reset the counters
        $this->currentGroupCounts = array('tests' => 0);

        // drop the microtime from the report
        unset($group['time']);

        // report the event
        $group['type'] = 'GroupStart';
        $this->reportEvent($group);
    }

    /**
     * End the group on the top of the stack.
     *
     * @return  void
    **/
    protected function reportGroupEnd()
    {
        // get the current group
        $group = array_pop($this->groupStack);

        // merge in the counters
        $group['count'] = $this->currentGroupCounts;
        $group['time'] = round(microtime(true) - $group['time'], 6);

        // if there is anything left on the stack, add to its counters
        if (count($this->groupStack) > 0) {
            // add the counts to the group on the top of the stack
            $top =& $this->groupStack[count($this->groupStack) - 1];

            if (isset($top['count'])) {
                foreach ($this->currentGroupCounts as $counter => $count) {
                    if (isset($top['count'][$counter])) {
                        $top['count'][$counter] += $count;
                    } else {
                        $top['count'][$counter] = $count;
                    }
                }
            } else {
                $top['count'] = $this->currentGroupCounts;
            }

            $this->currentGroupCounts = $top['count'];
        }

        // report the event
        $group['type'] = 'GroupEnd';
        $this->reportEvent($group);
    }

    /**
     * Paint a message.
     *
     * @param    string  Message type (Pass, Fail, Error, Exception, Skip).
     * @param    string  The message.
     * @returns  void
    **/
    protected function reportTest($type, $message)
    {
        if (is_array($message)) {
            // deal with new title/expectation/file/line message
            $result = array_merge(
                array(
                    'type' => 'test',
                    'result' => $type,
                    'time' => microtime(true) - $this->lastEventTime,
                ),
                $message,
                $this->current
            );
        } else {
            // deal with legacy plaintext message
            $result = array_merge(array(
                'type' => 'test',
                'result' => $type,
                'title' => $message,
                'time' => round(microtime(true) - $this->lastEventTime, 6),
                ),
                $this->current
            );
        }
        $this->reportEvent($result);
    }
}
