<?php

namespace Sins\TestCase;

// TODO build the expectations in expectation.php


class ExpectationTestCase extends \SimpleTestCase {

    protected $value;
    protected $got;
    protected $not;
    protected $dumper;

    function __construct() {
        parent::__construct();
        $this->dumper = new \SimpleDumper;
    }

    function expect($value)
    {
        $this->got = true;
        $this->value = $value;
        return $this;
    }

    private function got()
    {
        if ($this->got) {
            $this->got = false;
            return $this->value;
        } else {
            throw new \Exception('Expectation not set');
        }
    }

    public function not()
    {
        $this->not = true;
        return $this;
    }

    public function toBeFalse($message = null)
    {
        // get the variables
        $expected = false;
        $got = $this->got();

        // calculate the result
        $result = ($got === $expected);

        // invert the result if $this->not
        if ($this->not) {
            $result = ! $result;
            $this->not = false;
            $toBe = "not to be";
        } else {
            $toBe = "to be";
        }

        // get the text explanation
        $got = $this->dumper->describeValue($got);
        $expected = $this->dumper->describeValue($expected);
        $explain = "Expected $got $toBe $expected";

        if ($result) {
            return $this->_pass($message, $explain);
        } else {
            return $this->_fail($message, $explain);
        }
    }

    function toBeNull($message = null)
    {
        // get the variables
        $expected = null;
        $got = $this->got();

        // calculate the result
        $result = ($got === $expected);

        // invert the result if $this->not
        if ($this->not) {
            $result = ! $result;
            $this->not = false;
            $toBe = "not to be";
        } else {
            $toBe = "to be";
        }

        // get the text explanation
        $got = $this->dumper->describeValue($got);
        $expected = $this->dumper->describeValue($expected);
        $explain = "Expected $got $toBe $expected";

        if ($result) {
            return $this->_pass($message, $explain);
        } else {
            return $this->_fail($message, $explain);
        }
    }

    function toBe($expected, $message = null)
    {
        // get the variables
        $got = $this->got();

        // calculate the result
        $result = ($got === $expected);

        // invert the result if $this->not
        if ($this->not) {
            $result = ! $result;
            $this->not = false;
            $toBe = "not to be";
        } else {
            $toBe = "to be";
        }

        // get the text explanation
        $got = $this->dumper->describeValue($got);
        $expected = $this->dumper->describeValue($expected);
        $explain = "Expected $got $toBe $expected";

        if ($result) {
            return $this->_pass($message, $explain);
        } else {
            return $this->_fail($message, $explain);
        }
    }

    function toBeTrue($message = null)
    {
        // get the variables
        $expected = true;
        $got = $this->got();

        // calculate the result
        $result = ($got === $expected);

        // invert the result if $this->not
        if ($this->not) {
            $result = ! $result;
            $this->not = false;
            $toBe = "not to be";
        } else {
            $toBe = "to be";
        }

        // get the text explanation
        $got = $this->dumper->describeValue($got);
        $expected = $this->dumper->describeValue($expected);
        $explain = "Expected $got $toBe $expected";

        if ($result) {
            return $this->_pass($message, $explain);
        } else {
            return $this->_fail($message, $explain);
        }
    }

    function toBeTruthy($message = null)
    {
        // get the variables
        $expected = true;
        $got = $this->got();

        // calculate the result
        $result = ($got == $expected);

        // invert the result if $this->not
        if ($this->not) {
            $result = ! $result;
            $this->not = false;
            $toBe = "not to be";
        } else {
            $toBe = "to be";
        }

        // get the text explanation
        $got = $this->dumper->describeValue($got);
        $expected = $this->dumper->describeValue($expected);
        $explain = "Expected $got $toBe $expected";

        if ($result) {
            return $this->_pass($message, $explain);
        } else {
            return $this->_fail($message, $explain);
        }
    }


    /**
     *    @deprecated
     */
    function _pass($message, $explain=null) {
        $details = array('title' => $message);
        if ($explain !== null) {
            $details['explain'] = $explain;
        }
        $trace = $this->getTrace();
        if (is_array($trace) && count($trace) === 3) {
            $details['trace'] = $trace[1];
            $details['line'] = $trace[2];
        }

        $this->reporter->paintPass($details);
        return true;
    }

    /**
     *    Sends a fail event with a message.
     *    @param string $message        Message to send.
     *    @access public
     */
    function _fail($message, $explain = null) {
        $details = array('title' => $message);
        if ($explain !== null) {
            $details['explain'] = $explain;
        }
        $trace = $this->getTrace();
        if (is_array($trace) && count($trace) === 3) {
            $details['trace'] = $trace[1];
            $details['line'] = $trace[2];
        }

        $this->reporter->paintFail($details);
        return false;
    }

    /**
     *    Formats a PHP error and dispatches it to the
     *    reporter.
     *    @param integer $severity  PHP error code.
     *    @param string $message    Text of error.
     *    @param string $file       File error occoured in.
     *    @param integer $line      Line number of error.
     *    @access public
     */
    function error($severity, $message, $file, $line) {
        if (! isset($this->reporter)) {
            trigger_error('Can only make assertions within test methods');
        }
        $this->reporter->paintError(
                "Unexpected PHP error [$message] severity [$severity] in [$file line $line]");
    }

    /**
     *    Formats an exception and dispatches it to the
     *    reporter.
     *    @param Exception $exception    Object thrown.
     *    @access public
     */
    function exception($exception) {
        $this->reporter->paintException($exception);
    }

    /**
     *    Uses a stack trace to find the line of an assertion.
     *    @return string           Line number of first assert*
     *                             method embedded in format string.
     *    @access public
     */
    function getTrace() {
        $trace = new \SimpleStackTrace(array('toBe', 'pass', 'fail', 'skip'));
        $text = $trace->traceMethod();
        preg_match('@ at \[(.*) line (.*)\]@', $text, $matches);
        return $matches;
    }
}
