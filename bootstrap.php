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

namespace Sins;

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

    include __DIR__ . '/classes/Sins/Core.php';
    $core = new Core($local);

    $core->bootstrap();

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

// Create a request object.
$request = new Request();

// Inject it into the core so it can be used by error handling, logging.
// $app->share('request', $request);

// Create the front controller, passing the dependencies, and execute it.
$response = (new FrontController($local, $request))->getResponse();

// Inject it into the core so it can be used by error handling, logging.
// $app->share('response', $response);

// send the response
$response->send();

// and we are done
// $core->shutdown();

return;
