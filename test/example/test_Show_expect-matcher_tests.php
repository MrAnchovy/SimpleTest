<?php

class Test_Show_expect_matcher_tests extends \Sins\TestCase\ExpectMatcherTestCase
{

    function test_The_toBeNull_expectation_should_work() {
        $obj = new StdClass;
        $arr = array();
        $this->expect(null)        ->toBeNull('null should be null');
        $this->expect(true) ->not()->toBeNull('true should not be null');
        $this->expect(false)->not()->toBeNull('false should not be null');
        $this->expect('')   ->not()->toBeNull('an empty string should not be null');
        $this->expect(0)    ->not()->toBeNull('0 should not be null');
        $this->expect($obj) ->not()->toBeNull('an empty object should not be null');
        $this->expect($arr) ->not()->toBeNull('an empty array should not be null');
    }

    function test_The_toBeTrue_expectation_should_work() {
        $obj = new StdClass;
        $obj->true = true;
        $arr = array(true);
        $this->expect(null)  ->not()->toBeTrue('null should not be true');
        $this->expect(true)         ->toBeTrue('true should be true');
        $this->expect(false) ->not()->toBeTrue('false should not be true');
        $this->expect('true')->not()->toBeTrue('a non-empty string should not be true');
        $this->expect(1)     ->not()->toBeTrue('1 should not be true');
        $this->expect($obj)  ->not()->toBeTrue('an object should not be true');
        $this->expect($arr)  ->not()->toBeTrue('an array should not be true');
    }

    function test_The_toBeFalse_expectation_should_work() {
        $obj = new StdClass;
        $arr = array();
        $this->expect(null)  ->not()->toBeFalse('null should not be false');
        $this->expect(true)  ->not()->toBeFalse('true should not be false');
        $this->expect(false)        ->toBeFalse('false should be false');
        $this->expect('')    ->not()->toBeFalse('an empty string should not be false');
        $this->expect(0)     ->not()->toBeFalse('0 should not be false');
        $this->expect($obj)  ->not()->toBeFalse('an object should not be false');
        $this->expect($arr)  ->not()->toBeFalse('an array should not be false');
    }

    function test_The_toBeTruthy_expectation_should_work() {
        $this->expect(null)   ->not()->toBeTruthy('null should not be truthy');
        $this->expect(true)          ->toBeTruthy('true should be truthy');
        $this->expect(false)  ->not()->toBeTruthy('false should not be truthy');
        $this->expect('')     ->not()->toBeTruthy('an empty string should not be truthy');
        $this->expect('false')       ->toBeTruthy('a non-empty string should be truthy');
        $this->expect(0)      ->not()->toBeTruthy('0 should not be truthy');
        $this->expect(1)             ->toBeTruthy('1 should be truthy');
        $obj = new StdClass;
        $this->expect($obj)          ->toBeTruthy('an empty object should be truthy (see quirks)');
        $obj->false = false;
        $this->expect($obj)          ->toBeTruthy('a non-empty object should be truthy');
        $arr = array();
        $this->expect($arr)   ->not()->toBeTruthy('an empty array should not be truthy');
        $arr = array(false);
        $this->expect($arr)          ->toBeTruthy('a non-empty array should be truthy');
    }

    function test_Show_some_PHP_quirks() {
        $obj = new StdClass;
        $this->expect($obj)->toBeTruthy('An empty object is truthy...');
        $arr = (array) $obj;
        $this->expect($arr)->not()->toBeTruthy('... but an empty array is not.');

        $this->expect(0 == null)         ->toBeTrue('0 == null is true...');
        $this->expect(0 == '0')          ->toBeTrue('...as is 0 == "0"...');
        $this->expect('0' == null)->not()->toBeTrue('...but "0" == null is false!');

    }

    function test_Expectation_Tests_should_work_on_null() {
        $this->expect(null)->toBe(null, 'null should be null');
        $this->expect(false)->not()->toBe(null, 'false should not be null');
        $this->expect('')->not()->toBe(null, 'the empty string should not be null');
        $this->expect(0)->not()->toBe(null, '0 should not be null');
        $this->expect(array())->not()->toBe(null, 'an empty array should not be null');
        $this->expect(null)->not()->toBeFalse('null should not be false');
        $this->expect(null)->not()->toBeTrue('null should not be true');
    }

    function test_Expectation_Tests_should_work_on_true() {
        $this->expect(true)->toBe(true, 'null should equal null');
        $this->expect(true)->not()->toBe(false, 'null should not equal false');
        $this->expect(true)->not()->toBe(false, 'null should not equal false');
        $this->expect(true)->toBeNull('null should be null');
        $this->expect(true)->not()->toBeFalse('null should not be false');
    }

    function test_Expectation_Tests_should_work_on_false() {
        $this->expect(false)->toBe(false, 'false should equal false');
        $this->expect(false)->not()->toBe(null, 'false should not equal null');
        $this->expect(false)->toBeFalse('false should be false');
        $this->expect(false)->not()->toBeNull('false should not be null');
    }



}



class AllTests extends TestSuite {
    function AllTests() {
        $this->TestSuite('Simpletest tests');
        $this->addFile(__DIR__.'/test-simpletest-others.php');
    }
}

class TestSimpletestTests extends UnitTestCase {

    function test_Tests_should_work_on_null() {
        $this->assertNull(null, 'null should be null');
        $this->assertFalse(null, 'null should be falsy');
        $this->assertNotIdentical(null, false, 'null should not be false');
    }

    function test_Tests_on_booleans_should_work() {
        $this->assertTrue(true, 'true should be true');
        $this->assertFalse(false, 'false should be false');
        $this->assertNotIdentical(true, 1, 'true should not be identical to 1');
        $this->assertNotIdentical(false, 0, 'false should not be identical to 0');
        $this->assertNotNull(false, 'false should not be null');
    }
}

