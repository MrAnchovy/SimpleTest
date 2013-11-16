<?php

namespace Sins\TestCase;

// TODO build the expectations in expectation.php


class ExpectationTestCase extends \SimpleTestCase {

    protected $value;
    protected $expected;
    protected $not;

    function expect($value)
    {
        $this->expected = true;
        $this->value = $value;
        return $this;
    }

    private function expected()
    {
        if ($this->expected) {
            $this->expected = false;
            return $this->value;
        } else {
            throw new \Exception('Expectation not set');
        }
    }

    function not()
    {
        $this->not = true;
        return $this;
    }

    function toBeFalse($message = null)
    {
        if ($this->not) {
            $this->assert(new \FalseExpectation(), $this->expected() === false, "$message (%s)");
            $this->not = false;
        } else {
            $this->assert(new \TrueExpectation(), $this->expected() === false, "$message (%s)");
        }
    }

    function toBeNull($message = null)
    {
        if ($this->not) {
            $this->assert(new \FalseExpectation(), $this->expected() === null, "$message (%s)");
            $this->not = false;
        } else {
            $this->assert(new \TrueExpectation(), $this->expected() === null, "$message (%s)");
        }
    }

    function toBe($value, $message = null)
    {
        if ($this->not) {
            $this->assert(new \FalseExpectation(), $this->expected() === $value, "$message (%s)");
            $this->not = false;
        } else {
            $this->assert(new \TrueExpectation(), $this->expected() === $value, "$message (%s)");
        }
    }

    function toBeTrue($message = null)
    {
        if ($this->not) {
            $this->assert(new \FalseExpectation(), $this->expected() === true, "$message (%s)");
            $this->not = false;
        } else {
            $this->assert(new \TrueExpectation(), $this->expected() === true, "$message (%s)");
        }
    }

    function toBeTruthy($message = null)
    {
        if ($this->not) {
            $this->assert(new \FalseExpectation(), $this->expected() == true, "$message (%s)");
            $this->not = false;
        } else {
            $this->assert(new \TrueExpectation(), $this->expected() == true, "$message (%s)");
        }
    }
}
