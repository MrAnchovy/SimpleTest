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

namespace Sins;

class Core
{
    const VERSION = '2.0.0-dev';

    protected $classDir;
    protected $local;
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
        // do this first otherwise timestamping in any shutdown log may fail
        $this->bootstrapTimezone($this->local);
        $this->bootstrapAutoloader($this->local);
    }

    /**
     * Deal with unset default timezone.
    **/
    protected function bootstrapTimezone($local)
    {
        if (isset($local->timezone)) {
            // if it is set explicitly, use it
            date_default_set($local->timezone);
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
        $fileName .= $className . '.php';

        if (file_exists($fileName)) {
            include $fileName;
        }
    }

    /**
     * Register SPL class loader.
    **/
    public function bootstrapAutoloader($local)
    {
        $this->classDir = $local->baseDir.'classes'.DIRECTORY_SEPARATOR;
        spl_autoload_register(array($this, 'classAutoloader'));
    }

} // end class Core

abstract class Controller
{
    protected $request;
    protected $response;

    public function __construct(Local $local = null, Request $request = null, Response $response = null)
    {
        $this->local = $local;
        $this->request = $request;
        $this->response = $response;
    }

    protected function tryMethod($method)
    {
        try {
            $reflect = new \ReflectionMethod($this, $method);
            if ($reflect->getName() !== $method) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Execute an action (or the default action) on an optional id.
     *
     * @param  string  The type of action (Get, Post, Api).
     * @param  string  The action to execute (or null for the default action).
     * @param  string  The id of the entity to act on (or null).
    **/
    public function execute($type, $action, $id)
    {
        $execute = "execute$type";
        if ($action === null) {
            // execute the default action
            if ($id === null) {
                // first try executeTypeIndex()
                $method = "{$execute}Index";
            } else {
                // first try executeTypeId_id()
                $method = "{$execute}Id_$id";
            }
            if ($this->tryMethod($method)) {
                return $this->$method();
            }
            // now try executeType($id)
            if ($this->tryMethod($execute)) {
                return $this->$execute($id);
            }
        } else {
            // try executeTypeAction_action($id)
            $method = "{$execute}Action_$action";
            if ($this->tryMethod($method)) {
                return $this->$method($id);
            }
        }
        // TODO make this a 404 response
        throw new \Exception('Could not find the requested resource');
    }

} // end class Controller

class FrontController extends Controller
{
    protected $action;
    protected $controller;
    protected $extension;
    protected $id;
    protected $type;

    public function __construct(Local $local = null, Request $request = null, Response $response = null)
    {
        // No response is passed to the front controller
        parent::__construct($local, $request);
        $this->parseRequest();
    }

    /**
     * Get a response from a controller.
     *
     * The front controller must parse the request to determine what action to
     * perform on what entity, and what type of response to return.
    **/
    public function getResponse() {

        // TODO we need to get the application's namespace here
        $ns = 'Sins';
        if ($this->controller === null) {
            // try the default controller
            $controllerClass = "$ns\\Controller\\DefaultController";
        } else {
            // try the specified controller
            $controllerClass = "$ns\\Controller\\Controller_$this->controller";
        }
        // if (class_exists($class)) {
        $ok = false;
        try {
            $reflect = new \ReflectionClass($controllerClass);
            $ok = $reflect->getName() === $controllerClass;
        } catch (\Exception $e) {
        }

        if ($ok) {
            $controller = new $controllerClass($this->local, $this->request, $this->response);
            $controller->execute($this->type, $this->action, $this->id);
            return $this->response;
        } else {
            // TODO make this a 404 response
            throw new \Exception(strtr(
                'Could not find the requested controller [:controller]',
                array(':controller' => $this->controller)
            ));
        }
    }

    /**
     * Parse the request to get a route to a controller class and method.
     *
     * You may override this in \App\FrontController to implement a different
     * routing pattern.
     *
     * return  void
    **/
    protected function parseRequest()
    {
        $this->controller = null;
        $this->id         = null;
        $this->action     = null;
        $this->extension  = null;

        $api = $this->request->getQuery('api');

        if (empty($api)) {
            $this->type = 'Get';
            $this->response = new HttpResponse($this->request);

        } else {
            $this->type = 'Api';

            // although this could be done more clearly in stages, you just can't
            // beat a regex for parsing the whole thing at once.
            $pattern = '@^(?:(.*)(?:/(.*)(?:/(.*))?)?)(?:\.([^/.]+))?($)@U';
            //          @^                                                ($)@U   match start and end, ungreedy
            //            ([^/]*)                                                 match the controller       [1]
            //                   (?:/([^/]*)             )?                       match the id if any, ignoring the wrapper        [2]
            //                              (?:/([^/]*))?                         match the action if any, ignoring the wrapper    [3]
            //                                             (?:\.([^/.]+))?        match the extension if any, ignoring the wrapper [4]
            $pattern = '@^([^/]*)(?:/([^/]*)(?:/([^/]*))?)?(?:\.([^/.]+))?($)@U';
            preg_match($pattern, $api, $matches);

            $this->controller = $matches[1] === '' ? null : $matches[1];
            $this->id         = $matches[2] === '' ? null : $matches[2];
            $this->action     = $matches[3] === '' ? null : $matches[3];
            $this->extension  = $matches[4] === '' ? null : $matches[4];

            if ($this->id === null) {
                // if there is only one element, make it the id and use the default controller
                $this->id = $this->controller;
                $this->controller = null;
            }

            $this->response = new JsonResponse($this->request);
        }

        //    throw new Exception(
        //        'Invalid method :method for non-api request.',
        //        array(':method' => htmlspecialchars($method)),
        //        404);
    }

} // end class FrontController

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
    public $params;

    public function accepts($type = null) {
        $accepts = $this->getHeader('accept');
        if ($type === null) {
            return $accepts;
        } else {
            return (strpos($type, $accepts) !== false);
        }
    }

    /**
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

    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function getQuery($name = null, $default = null)
    {
        if ($name === null) {
            return $_GET;
        } elseif ($name === true) {
            return $_SERVER['QUERY_STRING'];
        } else {
            return array_key_exists($name, $_GET) ? $_GET[$name] : $default;
        }
    }

    public function getRemote()
    {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            if (isset($_SERVER['REMOTE_PORT'])) {
                return "$_SERVER[REMOTE_ADDR]:$_SERVER[REMOTE_PORT]";
            } else {
                return "$_SERVER[REMOTE_ADDR]";
            }
        } else {
            return null;
        }
    }

    public function getScheme()
    {
        return isset($_SERVER['HTTPS']) ? 'https' : 'http';
    }

    public function getBase()
    {
        return basename($_SERVER['SCRIPT_NAME']);
    }

} // end class Request

class Response
{
    protected $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

} // end class Response


class HttpResponse extends Response
{
    /**
     * The response body. If this is an array it is converted according to the
     * content type.
    **/
    public $body;

    /**
     * Content type.
    **/
    public $type = 'html';

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
    );

    /**
     * Supported content types.
    **/
    protected $inheritedContentTypes = array(
        'html'      => 'text/html',
        'text'      => 'text/plain',
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
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->contentTypes = array_merge($this->inheritedContentTypes, $this->contentTypes);
    }

    /**
     * Send this response.
    **/
    public function send()
    {
        if (!headers_sent()) {
            $this->sendHeaders();
        }
        // now send the body
        $method = "send_$this->type";
        $this->$method();
    }

    /**
     * Send the headers.
    **/
    protected function sendHeaders()
    {
        if (is_int($this->status)) {
            if (isset($this->statusCodes[$this->status])) {
                $status = "$this->status {$this->statusCodes[$this->status]}";
            } else {
                throw new \Exception(strtr(
                    'Status code [:code] not supported',
                    array(':code' => $this->status)
                ));
            }
        } else {
            $status = $this->status;
        }
        $this->sendHeader("HTTP/1.1 $status");
        $this->sendHeader('Content-Type', $this->contentTypes[$this->type]);
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
     * Send a text body.
    **/
    protected function send_text()
    {
        echo $this->body;
    }
}

class JsonResponse extends HttpResponse
{
    /**
     * Content type.
    **/
    public $type = 'json';

    /**
     *
    **/
    protected $contentTypes = array(
        'json'      => 'application/json',
        'jsonText'  => 'text/plain',        // used to return formatted json to a non-api request
    );

    public function __construct(Request $request)
    {
        parent::__construct($request);
        if (!$request->accepts('application/json')) {
            $this->type = 'jsonText';
        }
    }

    /**
     * Send a json body.
    **/
    protected function send_json()
    {
        $encoder = new Json;
        $encoder->encodeOptions = array();
        echo $encoder->encode($this->body);
    }

    /**
     * Send a json body formatted for a non-api (i.e. browser) request.
    **/
    protected function send_jsonText()
    {
        $encoder = new Json;
        $encoder->encodeOptions = array('JSON_PRETTY_PRINT' => 1);
        echo $encoder->encode($this->body);
    }
}
