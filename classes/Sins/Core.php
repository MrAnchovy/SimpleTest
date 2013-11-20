<?php
/**
 * These are the core classes for Sins.
 *
 * This file includes the following classeIt includes  is supplied with settings that should work "out of the box", but you will
 * want to change these - see the documentation for more information.
 *
 * @package    Sins
 * @link       https://github.org/MrAnchovy/Sins
 * @copyright  Copyright © 2013 [MrAnchovy](http://www.mranchovy.com/).
 * @license    [MIT](http://opensource.org/licenses/MIT)
**/

namespace Sins;

class Core
{
    const VERSION = '2.0.0-dev';

    protected $classDir;
    protected $local;

    /**
     * Constructor.
    **/
    public function __construct($local) {
        $this->local = $local;
        $this->bootstrapTimezone();
        $this->bootstrapExceptions();
    }

    /**
     * Deal with unset default timezone.
    **/
    protected function bootstrapTimezone()
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
     * Set up error and exception handling.
    **/
    protected function bootstrapExceptions() {
        Exception::$app = $this;
        set_exception_handler(array($this, 'exceptionHandler'));
        error_reporting(-1);
        // ini_set('display_errors', 0);
        ini_set('display_errors', 1);
        // throw new \Exception('oops'); // test
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
     * Exception handler.
    **/
    public function exceptionHandler(\Exception $e)
    {
        // rethrow as \Namespace\Exception
        throw new Exception($e);
    }

    /**
     * Register SPL class loader.
    **/
    public function registerClassAutoloader()
    {
        $this->classDir = $this->local->baseDir.'classes'.DIRECTORY_SEPARATOR;
        spl_autoload_register(array($this, 'classAutoloader'));
    }

} // end class Core


class Controller
{
    protected $app;
    protected $request;
    protected $response;

    public function __construct(Request $request, Response $response, Core $app)
    {
        $this->request = $request;
        $this->response = $response;
        $this->app = $app;
    }

    public function invoke()
    {
        $this->response->body = 'Hi there';
    }
} // end class Controller

class Exception extends \Exception
{
    /**
     * Because of the way exceptions are thrown this is the only effective way
     * to inject dependencies.
    **/
    public static $app;

    public function __construct($message = null, $vars = array(), $status = 500, $previous = null)
    {
        try {
            $message = strtr($message, $vars);
        } catch (\Exception $ee) {
            $message = $ee->getMessage() . " after $message";
            $status = 500;
        }
        parent::__construct($message, 0, $previous);
        if (isset(self::$app) && isset(self::$app->response)) {
            self::$app->response->body = $this->getMessage();
            self::$app->response->status = $status;
            self::$app->response->send();
        } else {
            header("HTTP/1.1 $status");
            header("Content-Type: text/plain");
            echo $this->getMessage();
        }
    }

} // end class Exception

class Request
{
    protected $app;
    public $path;
    protected $body;
    protected $params = array();
    protected $query = array();

    public function __construct(Core $app)
    {
        $this->app = $app;
        $this->path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : null;
    }

    public function getHeader($name, $default = null)
    {
        $name = 'HTTP_' . str_replace('-', '_', strtoupper($name));
        if (isset($_SERVER[$name])) {
            return $_SERVER[$name];
        } else {
            return $default;
        }
    }

    public function getBody()
    {
    }

    public function getParam($name = null, $default = null)
    {
        if (array_key_exists($this->params, $name)) {
            return $this->params[$name];
        } else {
            return $default;
        }
    }

    public function getQuery($name = null, $default = null)
    {
        if (array_key_exists($this->query, $name)) {
            return $this->query[$name];
        } else {
            return $default;
        }
    }

    public function parseHttp()
    {
        try {
            $this->method = $_SERVER['REQUEST_METHOD'];
            if ($this->method === 'GET') {
                $this->params = $_GET;
            } else {
                $this->params = $_POST;
                $this->query = $_GET;
            }
        } catch (\Exception $e) {
            // invalid request!
            throw new Exception('Bad Request', array(), 400, $e);
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
        'html' => 'text/html',
        'json' => 'application/json',
        'text' => 'text/plain',
        'xml'  => 'application/xml',
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
     *
    **/
    public function send()
    {
        try {
            if (is_int($this->status)) {
                if (isset($this->statusCodes[$this->status])) {
                    $status = "$this->status $this->statusCodes[$this->status]";
                } else {
                    throw new Exception(
                        'Status code [:code] not supported',
                        array(':code' => $this->status)
                    );
                }
            } else {
                $status = $this->status;
            }
            header("HTTP/1.1 $status");
            $contentType = $this->contentTypes[$this->contentType];
            header("Content-Type: $contentType");
            // send the other headers
            foreach($this->headers as $name => $value) {
                if (is_array($value)) {
                    foreach($value as $v) {
                        header("name: $value;");
                    }
                } else {
                    header("$name: $value;");
                }
            }
            // now send the body
            $method = "send_$this->contentType";
            $this->$method();
        } catch (Exception $e) {
            // rethrow a Sins exception
            throw $e;
        } catch (\Exception $e) {
            // convert an ordinary exception into a Sins exception
            throw new Exception($e->getMessage, array(), $e);
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
        try {
            echo json_encode($this->body);
        } catch (Exception $e) {
            // rethrow a Sins exception
            throw $e;
        } catch (\Exception $e) {
            // convert an ordinary exception into a Sins exception
            throw new Exception($e->getMessage, array(), $e);
        }
    }

} // end class Response


class Route
{

    public function __construct(Request $request, Core $app)
    {
        $this->app = $app;
        $this->request = $request;
    }

    public function dispatch(Response $response)
    {
        $controller = 'Sins\Controller';
        $controller = null;
        if (empty($controller)) {
            $controller = 'Sins\Controller\DefaultController';
        } elseif (!class_exists($controller)) {
            $controller = 'Sins\Controller\ErrorController';
        }
        // create the controller and invoke it
        (new $controller($this->request, $response, $this->app))->invoke();
    }

} // end class Route
