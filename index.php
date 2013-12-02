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

// We don't want to see anything nasty (set this to 1 to find out what is wrong
// if you are getting a blank screen).
ini_set('display_errors', 1);

// Save the time so we can monitor performance.
$startTime = microtime(true);

class DefaultLocal {
    // these properties are set in bootstrap.php so do not set them here
    public $baseDir;
    public $startTime;
    public $runmode = 'development';
    public $assetsPath ='assets/';
}

// include the file containing local settings and create the $local object
include __DIR__ . '/sins-local-default.php';
$local = new \Sins\Local;

// include the bootstrap file
include __DIR__ . '/bootstrap.php';

return;



$extra = explode('/', $_GET['q']);
$controller = (count($extra) > 0) ? array_shift($extra) : null;
$id = (count($extra) > 0) ? array_shift($extra) : null;



if ($controller === null) {
    echo 'index page';
} elseif ($controller = 'tests' && $id === null) {
    $tests = array();
    $directories = array(
        __DIR__.'/../app/tests',
    );
    foreach ($directories as $dir) {
        $tests = array_merge($tests, scan_for_tests($dir));
    }
    echo json_encode($tests);
} elseif ($controller = 'tests') {
    require $id;
}

function scan_for_tests($dir) {
    $dir = realpath($dir);
    $tests = array();
    $dh = dir($dir);
    while (false !== ($entry = $dh->read())) {
        $path = $dir.DIRECTORY_SEPARATOR.$entry;
        if (is_dir($path) && $entry !== '..' && $entry !== '.') {
            $tests = array_merge($tests, scan_for_tests($path));
        } elseif (stripos($entry, 'test') !== false) {
            $tests[] = $path;
        }
    }
    return $tests;
}

return;

