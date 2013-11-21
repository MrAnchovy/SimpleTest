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

// We don't want to see anything nasty (set this to 1 to find out what is wrong
// if you are getting a blank screen).
ini_set('display_errors', 0);

// Save the time so we can monitor performance.
$startTime = microtime(true);

// include the file containing local settings and create the $local object
include __DIR__ . '/sins-local-default.php';
$local = new \Sins\Local;

// include the bootstrap file
include __DIR__ . '/bootstrap.php';
