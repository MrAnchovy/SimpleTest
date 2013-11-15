<?php
/**
 * This is the core class for Sins.
 *
 * It includes  is supplied with settings that should work "out of the box", but you will
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
        $this->bootstrapExceptions();
        $this->request = new Request;
    }

    /**
     * Set up error and exception handling.
    **/
    public function bootstrapExceptions() {
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
    function classAutoloader($className)
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

        require $fileName;
    }

    /**
     * Exception handler.
    **/
    function exceptionHandler(\Exception $e)
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

}

class Exception extends \Exception
{
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

}

class Request
{
    public $headers = array();
    public $body;
    public $params = array();
    public $query;

    public function __construct()
    {
        $this->parseRequest();
    }
    public function parseRequest()
    {
        try {
            $this->method = $_SERVER['REQUEST_METHOD'];
            if ($this->method === 'GET') {
                $this->params = $_GET;
            } elseif ($this->method === 'GET') {
            }
        } catch (\Exception $e) {
            // invalid request!
            throw new Exception('Bad Request', array(), 400, $e);
        }
    }
}

class Response
{
    public $status;
    public $body;
    public function __construct()
    {
    }
    public function send()
    {
        foreach($this->headers as $name => $value) {
            if (is_array($value)) {
                foreach($value as $v) {
                    header("name: $value;");
                }
            } else {
                header("$name: $value;");
            }
        }
        echo $this->body;
    }
}
