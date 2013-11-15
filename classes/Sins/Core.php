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

    /**
     * Constructor
    **/
    public function __construct($local) {
        $this->local = $local;
    }

    /**
     * SPL class loader
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
     * Register SPL class loader
    **/
    public function registerClassAutoloader()
    {
        $this->classDir = $this->local->baseDir.'classes'.DIRECTORY_SEPARATOR;
        spl_autoload_register(array($this, 'classAutoloader'));
    }

}

class Request
{
    public function __construct()
    {
        $this->parseRequest();
    }
    public function parseRequest()
    {
        try {
            $this->method = $_SERVER['REQUEST_METHOD'];
            if ($this->method === 'GET') {
            } elseif ($this->method === 'GET') {
            }
            $this->params = $_GET;
        } catch (\Exception $e) {
            // invalid request!
            $ee = new Exception('Bad Request', array(), 400, $e);
        }
    }
}

class Response
{
    public function __construct()
    {
    }
}
