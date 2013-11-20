<?php
/**
 * These are the core classes for Scherzo.
 *
 * This file includes the following classes
 *   * Core
 *   * Controller
 *   * Exception
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
        $app = new Container;
        $app->share('local', $this->local);
        $this->bootstrapTimezone($app);
        $this->bootstrapExceptions($app);
        return $app;
    }

    /**
     * Set up error and exception handling.
    **/
    protected function bootstrapExceptions($app) {
        Exception::$app = $app;
        set_exception_handler(array($this, 'exceptionHandler'));
        error_reporting(-1);
        // ini_set('display_errors', 0);
        ini_set('display_errors', 1);
        // throw new \Exception('oops'); // test
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

    public function __construct(Request $request, Response $response, Container $app)
    {
        $this->request = $request;
        $this->response = $response;
        $this->app = $app;
    }

    public function invoke()
    {
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
    public $route;
    protected $body;
    protected $params = array();
    protected $query = array();

    public function __construct(Container $app)
    {
        $this->app = $app;
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
        return array_key_exists($this->params, $name) ? $this->params[$name] : $default;
    }

    public function getQuery($name = null, $default = null)
    {
        return array_key_exists($this->query, $name) ? $this->query[$name] : $default;
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
            // rethrow a Scherzo exception
            throw $e;
        } catch (\Exception $e) {
            // convert an ordinary exception into a Scherzo exception
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
            // rethrow a Scherzo exception
            throw $e;
        } catch (\Exception $e) {
            // convert an ordinary exception into a Scherzo exception
            throw new Exception($e->getMessage, array(), $e);
        }
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
        (new $class($this->request, $response, $this->app))->invoke();
        return $this; // chainable
    }

} // end class Route
