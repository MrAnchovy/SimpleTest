<?php

abstract class Demonstrate_SimpleTest_test extends UnitTestCase
{

    protected $explanation = ' (Explanation: %s)';

    protected function explain($message)
    {
        return "$message$this->explanation";
    }
}

class Test_Demonstrate_SimpleTest_assertTrue extends Demonstrate_SimpleTest_test
{
    function test_Show_intended_operation()
    {
        $this->assertTrue(true, $this->explain('true should be true'));
        $this->assertTrue(false, $this->explain('false should not be true'));
    }

    function test_Show_some_pitfalls()
    {
        $this->assertTrue('false', $this->explain('LOOK OUT - anything truthy passes'));
    }

}

class Test_Demonstrate_SimpleTest_assertFalse extends Demonstrate_SimpleTest_test
{
    function test_Show_intended_operation() {
        $this->assertFalse(false, $this->explain('false should be false'));
        $this->assertFalse(true, $this->explain('true should not be false'));
    }

    function test_Show_some_pitfalls() {
        $this->assertFalse(0, $this->explain('(int) 0 passes'));
        $this->assertFalse('', $this->explain('an empty string passes...'));
        $this->assertFalse(array(), $this->explain('an empty array passes...'));
        $object = new StdClass;
        $this->assertFalse($object, $this->explain('...but an empty object fails'));
    }
}

class Test_Demonstrate_SimpleTest_assertEqual extends Demonstrate_SimpleTest_test
{
    function test_Show_intended_operation()
    {
        $this->assertEqual(1, 1, '1 should equal 1 %s');
    }

    function test_Show_some_pitfalls()
    {
        $this->assertEqual(false, '0', 'LOOK OUT - types are ignored so false equals "0" %s');
    }

}
