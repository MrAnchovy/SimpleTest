<?php
/**
 * Report tests in a json format.
 *
 * @package    Sins
 * @copyright  Copyright © 2013 [MrAnchovy](http://www.mranchovy.com/).
 * @license    [MIT](http://opensource.org/licenses/MIT)
**/

namespace Sins\Reporter;

// require_once(\Sins\Interface(__FILE__) . '/scorer.php');
// require_once(dirname(__FILE__) . '/arguments.php');

// class JsonReporter extends \SimpleReporter
class JsonReporter
{

    protected $data;
    protected $groupStack = array();
    protected $current = array();
    protected $currentGroupCounts = array();


    /**
     * The legacy test case calls this method to let the reporter modify the
     * invoker. Why?
     *
     * @REVISIT       Rewrite the test runner and delete this method.
     * @param   SimpleInvoker $invoker   Individual test runner.
     * @return  SimpleInvoker           Wrapped test runner.
    **/
    public function createInvoker($invoker) {
        return $invoker;
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
     * This is called by the runner at the start of each 'case' (i.e. a class)
     *
     * @param   string  The class name.
     * @return  void
    **/
    public function paintCaseStart($title)
    {
        $this->current['class'] = $title;
        $this->startGroup($title, 'class');
    }

    /**
     * This is called by the runner at the end of each 'case' (i.e. a class)
     *
     * @param   string  The class name.
     * @return  void
    **/
    public function paintCaseEnd($title)
    {
        $this->endGroup();
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
        $this->startGroup($title, 'method');
    }

    /**
     * This is called by the runner at the end of each method.
     *
     * @param   string  The method name.
     * @return  void
    **/
    public function paintMethodEnd($title)
    {
        $this->endGroup();
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
                $this->startReport($title, $size);
                $this->startGroup($title, 'testsuite');
                break;

            case 1 :
                // this is a new file
                $this->startGroup($title, 'file');
                $this->current['file'] = $title;
                break;

            default:
                $this->startGroup($title, 'unknown');
        }
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
        $this->endGroup();
    }

    /**
     * Start a group for collating and counting test results.
     *
     * @param   string  Group title.
     * @param   string  Group type (testsuite, class, method).
     * @return  void
    **/
    protected function startGroup($title, $type)
    {
        // set up the event details
        $group = array(
            'groupType' => $type,
            'title' => $title,
        );

        // put this on the stack
        array_push($this->groupStack, $group);

        // reset the counters
        $this->currentGroupCounts = array('tests' => 0);

        // report the event
        $group['type'] = 'GroupStart';
        $this->reportEvent($group);
    }

    /**
     * End the group on the top of the stack.
     *
     * @return  void
    **/
    protected function endGroup()
    {
        // get the current group
        $group = array_pop($this->groupStack);

        // merge in the counters
        $group['count'] = $this->currentGroupCounts;

        $top = count($this->groupStack) - 1;

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

        // report the event
        $group['type'] = 'GroupEnd';
        $this->reportEvent($group);
    }

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
     * This is called by the test runner when there has been an unexpected error
     * (not an exception or an expected error) during test execution.
     *
     * @param   string  The message.
     * @return  void
    **/
    public function paintError($message)
    {
        $this->increment('error');
        $this->paintTypedMessage('Error', $message);
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
        $this->paintTypedMessage('Exception', $message);
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
        $this->paintTypedMessage('Fail', $message);
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
        $this->paintTypedMessage('Pass', $message);
    }

    /**
     * This is called by the test runner when a test has been skipped.
     *
     * @param    Exception  An Exception object.
     * @returns  void
    **/
    public function paintSkip($message) {
        $this->increment('skip');
        $this->paintTypedMessage('Skip', $message);
    }

    /**
     * Paint a message.
     *
     * @param    string  Message type (Pass, Fail, Error, Exception, Skip).
     * @param    string  The message.
     * @returns  void
    **/
    protected function paintMessage($message)
    {
        $this->paintTypedMessage('Unknown', $message);
    }

    /**
     * Paint a message.
     *
     * @param    string  Message type (Pass, Fail, Error, Exception, Skip).
     * @param    string  The message.
     * @returns  void
    **/
    protected function paintTypedMessage($type, $message)
    {
        echo "[$type: ";
        print "$message : ";
        echo $this->getTrace();
        echo "]\n";
    }


    /**
     *    Accessor for internal test stack. For
     *    subclasses that need to see the whole test
     *    history for display purposes.
     *    @return array     List of methods in nesting order.
     *    @access public
     */
    protected function getTrace()
    {
        extract($this->current);
        return "File:$file > Class:$class > Method:$method";
    }








    /**
     * Add an event to the report.
     *
     * @param    array  The event.
     * @returns  void
    **/
    protected function reportEvent($event)
    {
        $this->data[] = $event;
        print_r($event);
    }

    /**
     * Paint the header - this is not part of the interface with the runner.
     *
     * @TODO            Use $response instead of sending header directly.
     * @param   string  The title of the test suite.
     * @return  void
    **/
    protected function startReport($name, $size) {
        header('Content-Type: text/plain');
    }






    /**
     * Paint the footer - called at the end of the test suite.
     *
     * @param    string  The title of the test suite.
     * @returns  void
    **/
    protected function paintFooter($name)
    {

        echo "[Footer: $name]\n";
        return;

        $colour = ($this->getFailCount() + $this->getExceptionCount() > 0 ? "red" : "green");
        print "<div style=\"";
        print "padding: 8px; margin-top: 1em; background-color: $colour; color: white;";
        print "\">";
        print $this->getTestCaseProgress() . "/" . $this->getTestCaseCount();
        print " test cases complete:\n";
        print "<strong>" . $this->getPassCount() . "</strong> passes, ";
        print "<strong>" . $this->getFailCount() . "</strong> fails and ";
        print "<strong>" . $this->getExceptionCount() . "</strong> exceptions.";
        print "</div>\n";
        print "</body>\n</html>\n";
    }

}
