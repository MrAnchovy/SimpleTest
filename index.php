<?php
/**
 * This is the entry point for web access to the Sins test application.
 *
 * It is supplied with settings that should work "out of the box", but you will
 * want to change these - see the documentation for more information.
 *
 * @package    Sins
 * @link       https://github.org/MrAnchovy/Sins
 * @copyright  Copyright © 2013 [MrAnchovy](http://www.mranchovy.com/).
 * @license    [MIT](http://opensource.org/licenses/MIT)
**/

namespace Sins;
use Exception;

// We don't want to see anything nasty (set this to 1 to find out what is wrong
// if you are getting a blank screen).
ini_set('display_errors', 1);

// Save the time so we can monitor performance.
$startTime = microtime(true);

class DefaultLocal {

    const DEVELOPMENT = 'development';
    const TESTING     = 'testing';
    const STAGING     = 'staging';
    const PRODUCTION  = 'production';

    public function init()
    {
    }

    final public function _init()
    {
        $this->runmode     = self::PRODUCTION;
        $this->baseUrl     = $_SERVER['REQUEST_URI'];
        $this->apiUrl      = $_SERVER['REQUEST_URI'];
        $this->assetsUrl   = 'assets/';
        $this->testBaseDir = realpath(__DIR__);
        $this->init();
    }
}

// include the file containing local settings and create the $local object
try {
    include __DIR__ . '/sins-local-default.php';
    $local = new Local;
} catch (Exception $e) {
    $local = new DefaultLocal;
}

// include the bootstrap file
include __DIR__ . '/bootstrap.php';
