<?php
/**
 * This file launches the Sins browser-based test framework.
 *
 * It is supplied with settings that should work "out of the box", but you will
 * want to change these - see the documentation for more information.
 *
 * @package    Sins
 * @version    2.0.0-dev
 * @link       https://github.org/MrAnchovy/Sins
 * @copyright  Copyright © 2013 [MrAnchovy](http://www.mranchovy.com/).
 * @license    [MIT](http://opensource.org/licenses/MIT)
**/

namespace SinsScherzo;

if (!isset($local)) {
    // if there is no $local we can't do anything, but we shouldn't have got here anyway
    header('HTTP/1.1 404 Not Found');
    header('Content-Type: text/plain');
    echo 'Not found';
    exit();
}

try {
    // set the start time for logging
    if (isset($startTime)) {
        $local->startTime = $startTime;
        unset($startTime);
    } else {
        $local->startTime = microtime(true);
    }

    // set the directory containing Sins
    $local->baseDir = __DIR__ . DIRECTORY_SEPARATOR;

    // try and autoload the core class - if you want to override the core, do it here
    if (class_exists('\SinsScherzo\Core')) {
        $core = new Core($local);
    } else {
        // we have no autoloader so we will have to load it manually
        include __DIR__ . '/classes/SinsScherzo/Core.php';
        // and register our own autoloader
        $core = new Core($local);
        $core->registerClassAutoloader();
    }

    $app = $core->bootstrap();

} catch (\Exception $e) {
    // if we get here we cannot handle exceptions normally so this is all we can do
    if (isset($local) && isset($local->runmode)
        && ($local->runmode === 'development' || $local->runmode === 'testing')) {
        throw $e;
    } else {
        header('HTTP/1.1 500 Server Error');
        header('Content-Type: text/plain');
        echo 'Server Error';
    }
    exit(1);
}

// that's the end of the bootstrapping, now we can get on with the request

// Create a request object and populate it from the HTTP request.
$request = new Request($core);

// Create a response object of the right type - we do this now so it can be used
// to report any errors in parsing the request.
$response = new Response($core);

// Parse the request
$request->parseHttp();

// create a route and dispatch it
(new Route($app))->parse($request)->dispatch($response);

// send the response
$response->send();

// and we are done
$core->shutdown();

return;
