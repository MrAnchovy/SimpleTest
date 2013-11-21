<?php
/**
 * These are the core classes for Scherzo.
 *
 * This file includes the following classes
 *   * Core
 *   * Controller
 *   * Exception
 *   * JsonEncoder
 *   * Request
 *   * Response
 *   * Route
 *
 * @package    Scherzo
 * @link       https://github.org/MrAnchovy/Scherzo
 * @copyright  Copyright © 2013 [MrAnchovy](http://www.mranchovy.com/).
 * @license    [MIT](http://opensource.org/licenses/MIT)
**/

namespace SinsScherzo;

class Core
{
    const VERSION = '2.0.0-dev';

    protected $classDir;
    protected $local;
    protected $app;
    public $request;
    public $response;

    /**
     * Constructor.
    **/
    public function __construct($local) {
        $this->local = $local;
    }

    /**
     * Bootstrap the core.
    **/
    public function bootstrap() {
        $this->app = new Container;
        $this->app->share('local', $this->local);
        // do this first otherwise timestamping in any shutdown log may fail
        $this->bootstrapTimezone($this->app);
        $this->bootstrapExceptions($this->app);
        return $this->app;
    }

    /**
     * Set up error and exception handling.
    **/
    protected function bootstrapExceptions($app) {
        error_reporting(-1);
        // REVISIT there is probably a best order to do these in
        set_exception_handler(array($this, 'exceptionHandler'));
        set_error_handler(array($this, 'errorHandler'), -1);      // handle all errors
        register_shutdown_function(array($this, 'shutdownHandler'));
    }

    /**
     * Deal with unset default timezone.
    **/
    protected function bootstrapTimezone($app)
    {
        if (isset($this->local->timezone)) {
            // if it is set explicitly, use it
            date_default_set($this->local->timezone);
        } else {
            // date.timezone is the only other way to set it in PHP >= 5.4.0
            if (!ini_get('date.timezone')) {
                date_default_timezone_set('UTC');
            }
        }
    }

    /**
     * SPL class loader.
    **/
    public function classAutoloader($className)
    {
        $className = ltrim($className, '\\');
        $fileName  = $this->classDir;
        $namespace = '';
        if ($lastNsPos = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName  .= str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        if (file_exists($fileName)) {
            include $fileName;
        }
    }

    /**
     * Error handler.
    **/
    public function errorHandler()
    {
        // rethrow as \Namespace\Exception
        $e = new Exception();
        $e->fromError(func_get_args());
        throw $e;
    }

    /**
     * Exception handler.
    **/
    public function exceptionHandler(\Exception $e)
    {
        try {
            $controller = new ErrorController($this->app, $this->request, $this->response);
            $controller->setException($e);
            $controller->invoke();
            $this->response->send();
        } catch (\Exception $e) {
            echo 'Error in exception handler'. print_r($e);
        }
    }

    /**
     * Register SPL class loader.
    **/
    public function registerClassAutoloader()
    {
        $this->classDir = $this->local->baseDir.'classes'.DIRECTORY_SEPARATOR;
        spl_autoload_register(array($this, 'classAutoloader'));
    }

    /**
     * Shutdown handler.
    **/
    public function shutdownHandler()
    {
        if ($error = error_get_last()) {
            $e = new Exception();
            $e->fromLastError($error);
            $this->exceptionHandler($e);
        }

        flush();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    /**
     * Shutdown handler.
    **/
    public function shutdown()
    {
        flush();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        // do lengthy stuff here
    }

//        Kohana::exception_handler(new ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']));

} // end class Core

class Container
{
    protected $param;
    protected $shared;

    public function share($name, $share, $params = null)
    {
        if (is_object($share)) {
            $this->shared[$name] = $share;
        } else {
            $this->shared[$name] = array($share, $params);
        }
    }

    public function shared($name)
    {
        if (isset($this->shared[$name])) {
            $share = $this->shared[$name];
        } else {
            return null;
        }
        if (is_object($share)) {
            return $share;
        } elseif (is_array($share)) {
            $this->shared[$name] = new $share[0]($this, $share[1]);
        } else {
            $this->shared[$name] = new $share($this);
        }
        return $this->shared[$name];
    }

    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }

    public function param($name)
    {
        return array_key_exists($this->param, $name) ? $this->param[$name] : null;
    }
}

abstract class Controller
{
    protected $app;
    protected $request;
    protected $response;

    public function __construct(Container $app = null, Request $request = null, Response $response = null)
    {
        $this->app = $app;
        $this->request = $request;
        $this->response = $response;
    }

    public function invoke()
    {
    }
} // end class Controller


/**
 * Decode from json with options and error handling.
**/
class JsonDecoder
{
    /**
     * Decodes json hashes to associative arrays (rather than objects).
    **/
    public $assoc = true;

    /**
     * Array of default options for json_decode - these have PHP constant names
     * as keys, although only JSON_BIGINT_AS_STRING is implemented in 2013.
    **/
    protected $defaults = array(
        'JSON_BIGINT_AS_STRING' => true,
    );

    /**
     * Depth parameter for json_decode - this must be set to a value because
     * of the ordering of arguments in json_decode().
    **/
    public $depth = 512;

    /**
     * Array of options for json_decode - see $defaults.
    **/
    public $options = array();

    /**
     * If true, options that don't exist throw an exception, otherwise they are ignored.
    **/
    public $strict = false;

    /**
     * Decode from json with options and error handling.
    **/
    public function decode($body)
    {
        $bitmask = 0;
        $settings = array_merge($this->defaults, $this->options);
        foreach ($settings as $option => $value) {
            if ($value && defined($option)) {
                $bitmask = $bitmask | constant($option);
            } elseif ($value && $this->strict) {
                throw new Exception(
                    'Constant :const not available in PHP :ver',
                    array(':const' => $option, ':ver' => phpversion())
                );
            }
        }
        $decoded = json_decode($body, $this->assoc, $this->depth, $bitmask);
        if (json_last_error === JSON_ERROR_NONE) {
            return $decoded;
        }
        throw new Exception(
            'Json decoding error. :msg',
            array(':msg' => json_last_error()),
            400 // this is a bad request error
        );
    }
}

class JsonEncoder
{
    /**
     * Array of default options for json_encode - these have PHP constant names
     * as keys - some examples shown below (these two are useful for displaying
     * JSON as HTML).
    **/
    protected $defaults = array(
        // 'JSON_PRETTY_PRINT' => true,
        // 'JSON_HEX_TAG'      => true,
    );

    /**
     * Depth parameter for json_encode.
    **/
    public $depth = null;

    /**
     * Array of options for json_encode - see $defaults.
    **/
    public $options = array();

    /**
     * If true, options that don't exist throw an exception, otherwise they are ignored.
    **/
    public $strict = false;

    /**
     * Encode into json with options and error handling.
    **/
    public function encode($body)
    {
        $bitmask = 0;
        $settings = array_merge($this->defaults, $this->options);
        foreach ($settings as $option => $value) {
            if ($value && defined($option)) {
                $bitmask = $bitmask | constant($option);
            } elseif ($value && $this->strict) {
                throw new Exception(
                    'Constant :const not available in PHP :ver',
                    array(':const' => $option, ':ver' => phpversion())
                );
            }
        }
        
        // get the encoded string
        if ($this->depth === null) {
            $encoded = json_encode($body, $bitmask);
        } else {
            $encoded = json_encode($body, $bitmask, $this->depth);
        }

        // if there was no error return the encoded string
        if (json_last_error === JSON_ERROR_NONE) {
            return $encoded;
        }
        
        // fail if there was an error
        throw new Exception(
            'Json encoding error. :msg',
            array(':msg' => json_last_error())
        );
    }
}




/**
 * HTTP request handling.
 *
 * The request is mainly dealt with by lazy-loading to avoid redundant processing.
**/
class Request
{

    /**
     * Request parameters.
    **/
    public $params = array();
    public $path;
    public $query  = array();
    public $route;

    /**
     * Constructor - inject the request into the core so it can be used by shutdown
     * and error handlers.
    **/
    public function __construct(Core $app)
    {
        $app->request = $this;
    }

    /**
     * Get the request body.
     *
     * The stream
     *
     * @TODO            Treat it as a stream and stop reading after a while to guard
     *                  against DOS
     * @return  string  The request body (for content types other than
     *                  multipart/form-data).
    **/
    public function getBody()
    {
        static $body;
        if ($body === null) {
            $body = file_get_contents('php://input');
        }
        return $body;
    }

    /**
     * Get a request body.
     *
     * @TODO            Treat it as a stream and stop reading after a while to guard
     *                  against DOS
     * @return  string  The request body (for content types other than
     *                  multipart/form-data).
    **/
    public function getHeader($name, $default = null)
    {
        $name = 'HTTP_' . str_replace('-', '_', strtoupper($name));
        if (isset($_SERVER[$name])) {
            return $_SERVER[$name];
        } else {
            return $default;
        }
    }

    public function getParam($name = null, $default = null)
    {
        if ($name === null) {
            return $this->params;
        } else {
            return array_key_exists($this->params, $name) ? $this->params[$name] : $default;
        }
    }

    public function getQuery($name = null, $default = null)
    {
        if ($name === null) {
            return $this->query;
        } else {
            return array_key_exists($this->query, $name) ? $this->query[$name] : $default;
        }
    }

    public function parseHttp()
    {
        try {
            $this->path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : null;
            $this->method = $_SERVER['REQUEST_METHOD'];
            if ($this->method === 'GET') {
                $this->params = $_GET;
            } else {
                $this->params = $_POST;
                $this->query  = $_GET;
            }
        } catch (\Exception $e) {
            // invalid request!
            throw new Exception('Bad Request', array(), 400, $e);
        }
    }

    protected function parseJson()
    {
        $decoder = new JsonDecoder;
        $decoder->assoc = true; // we want an array
        $decoded = $decoder->decode($this->getBody());
        if (is_array($decoded)) {
            $this->params = $decoded;
        } else {
            $this->params = array();
        }
    }

} // end class Request

class Response
{

    /**
     * The response body. If this is an array it is converted according to the
     * content type.
    **/
    public $body;

    /**
     * Content type.
    **/
    public $contentType = 'html';

    /**
     * Array of headers to be sent. Do NOT set the content type here, it will
     * be overwritten.
    **/
    public $headers = array();

    /**
     * HTTP status code or message. If this is a string it is sent unamended,
     * otherwise it should be an integer and the correct status line is created.
    **/
    public $status;

    /**
     * Supported content types.
    **/
    protected $contentTypes = array(
        'html'      => 'text/html',
        'json'      => 'application/json',
        'jsonText'  => 'text/plain',        // used to return formatted json to a non-api request
        'text'      => 'text/plain',
        'xml'       => 'application/xml',
        'xmlText'   => 'text/plain',        // used to return formatted xml to a non-api request
        'yaml'      => 'text/plain',
    );

    /**
     * Supported HTTP status codes.
    **/
    protected $statusCodes = array(
        200 => 'OK',
        201 => 'Created',    // for API use when something has been created
        202 => 'Accepted',   // for API use when something has been queued
        301 => 'Moved Permanently',
        303 => 'See Other',  // use to redirect following a <form> post
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        403 => 'Forbidden',
        429 => 'Too Many Requests', // use to throttle a user
        404 => 'Not Found',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable', // use when busy
    );

    /**
     * Constructor - inject the response into the core so it can be used by shutdown
     * and error handlers.
    **/
    public function __construct(Core $app)
    {
        $app->response = $this;
    }

    /**
     * Send this response.
    **/
    public function send()
    {
        if (is_int($this->status)) {
            if (isset($this->statusCodes[$this->status])) {
                $status = "$this->status {$this->statusCodes[$this->status]}";
            } else {
                throw new Exception(
                    'Status code [:code] not supported',
                    array(':code' => $this->status)
                );
            }
        } else {
            $status = $this->status;
        }
        $this->sendHeader("HTTP/1.1 $status");
        $this->sendHeader('Content-Type', $this->contentTypes[$this->contentType]);
        // send the other headers
        foreach($this->headers as $name => $value) {
            if (is_array($value)) {
                foreach($value as $v) {
                    $this->sendHeader($name, $v);
                }
            } else {
                $this->sendHeader($name, $value);
            }
        }
        // now send the body
        $method = "send_$this->contentType";
        $this->$method();
    }

    /**
     * Send an HTTP header: you can override this to create a mock object for testing.
    **/
    protected function sendHeader($name, $value = null)
    {
        if ($value === null) {
            header($name);
        } else {
            header("$name: $value");
        }
    }

    /**
     * Send an html body.
    **/
    protected function send_html()
    {
        echo $this->body;
    }

    /**
     * Send a json body.
    **/
    protected function send_json()
    {
        $encoder = new JsonEncoder;
        echo (new JsonEncoder)->encode($this->body);
    }

    /**
     * Send a json body formatted for a non-api (i.e. browser) request.
    **/
    protected function send_jsonText()
    {
        $encoder = new JsonEncoder;
        $encoder->options = array('JSON_PRETTY_PRINT');
        echo $encoder->encode($this->body);
    }

} // end class Response

class Route
{

    protected $app;
    protected $request;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    public function parse(Request $request)
    {
        $this->request = $request;
        $this->controller = null;
        $this->id = null;
        $this->extra = array();
        $request->route = $this;
        return $this; // chainable
    }

    public function dispatch(Response $response)
    {
        $ns = 'Sins';
        $controller = null;

        if ($controller === null) {
            // try the application's default controller
            $class = "$ns\\Controller\\DefaultController";
        } else {
            // try the specified application controller
            $class = "$ns\\Controller\\Controller_$controller";
        }
        if (!class_exists($class)) {
            if ($controller === null) {
                // the default controller was requested but the application
                // doesn't have one so use the default default
                $class = __NAMESPACE__ . '\Controller\DefaultController';
            } else {
                // the specified controller doesn't exist
                throw new Exception(
                    'The specified controller :name doesn\'t exist',
                    array(':name' => $controller),
                    404
                );
            }
        }
        // create the controller and invoke it
        (new $class($this->app, $this->request, $response))->invoke();
        return $this; // chainable
    }

} // end class Route
