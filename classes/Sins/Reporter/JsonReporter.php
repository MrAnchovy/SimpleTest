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

class JsonReporter extends \SimpleReporter {


    /**
     * Constructor - chains parent for counting.
     *
     * @returns  void
    **/
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Paint the header - called at the start of the test suite.
     *
     * @param    string  The title of the test suite.
     * @returns  void
    **/
    function paintHeader($name) {
        $this->data = array();
        header('Content-Type: text/plain');
        echo "[Header: $name]\n";
    }

    function paintClassStart($name)
    {
        parent::paintClassStart($name);
        echo "[ClassStart: $name]\n";
    }

    function paintClassEnd($name)
    {
        parent::paintClassEnd($name);
        echo "[ClassEnd: $name]\n";
    }

    function paintMethodStart($name)
    {
        parent::paintMethodStart($name);
        echo "[MethodStart: $name]\n";
    }

    function paintMethodEnd($name)
    {
        parent::paintMethodEnd($name);
        echo "[MethodEnd: $name]\n";
    }

    function paintGroupStart($name, $size)
    {
        parent::paintGroupStart($name, $size);
        echo "[GroupStart (size $size): $name]\n";
    }

    function paintGroupEnd($name)
    {
        parent::paintGroupEnd($name);
        echo "[GroupEnd: $name]\n";
    }

    /**
     * Paint the footer - called at the end of the test suite.
     *
     * @param    string  The title of the test suite.
     * @returns  void
    **/
    function paintFooter($name)
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

    /**
     * Paint a fail message.
     *
     * @param    string  The message.
     * @returns  void
    **/
    function paintFail($message)
    {
        parent::paintFail($message);
        $this->paintTypedMessage('Fail', $message);
    }

    /**
     * Paint a pass message.
     *
     * @param    string  The message.
     * @returns  void
    **/
    function paintPass($message)
    {
        parent::paintPass($message);
        $this->paintTypedMessage('Pass', $message);
    }

    /**
     * Paint a message.
     *
     * @param    string  Message type (Pass, Fail, Error, Exception, Skip).
     * @param    string  The message.
     * @returns  void
    **/
    function paintMessage($message)
    {
        parent::paintMessage($message);
        $this->paintTypedMessage('Unknown', $message);
    }

    /**
     * Paint a message.
     *
     * @param    string  Message type (Pass, Fail, Error, Exception, Skip).
     * @param    string  The message.
     * @returns  void
    **/
    function paintTypedMessage($type, $message)
    {
        echo "[$type: ";
        $breadcrumb = $this->getTestList();
        print "$message : ";
        print implode(" -> ", $breadcrumb);
        echo "]\n";
    }

    /**
     * Paint an error message.
     *
     * @param    string  The message.
     * @returns  void
    **/
    function paintError($message)
    {
        parent::paintError($message);
        $this->paintTypedMessage('Error', $message);
    }

    /**
     * Paint an exception message.
     *
     * @param    Exception  An Exception object.
     * @returns  void
    **/
    function paintException($e) {
        parent::paintException($e);
        $message = get_class($e)
            . $e->getMessage()
            . ' in ' . $e->getFile()
            . ' line ' . $e->getLine();
        $this->paintTypedMessage('Exception', $message);
    }

    /**
     *    Prints the message for skipping tests.
     *    @param string $message    Text of skip condition.
     *    @access public
     */
    function paintSkip($message) {
        parent::paintSkip($message);
        $this->paintTypedMessage('Skip', $message);
    }

}
