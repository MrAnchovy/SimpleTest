<?php

namespace SinsScherzo;

class Exception extends \Exception
{
    public $status = 500;

    public $errorTypes = array(
        1 => 'E_ERROR',
        2 => 'E_WARNING',
        4 => 'E_PARSE',
        8 => 'E_NOTICE',
        16 => 'E_CORE_ERROR',
    );

    public function __construct($message = null, $vars = array(), $status = 500, $previous = null)
    {
        try {
            $message = strtr($message, $vars);
        } catch (\Exception $ee) {
            $message = $ee->getMessage() . " after $message";
            $this->status = 500;
        }
        parent::__construct($message, 0, $previous);
    }

    /**
     * @param  int      Error level.
     * @param  string   Message.
     * @param  string   File where the error occurred.
     * @param  int      Line where the error occurred.
     * @param  context  Array of variables in scope when the error occurred.
    **/
    public function fromError($args)
    {
        $this->level = $args[0];
        $this->message = $args[1];
        $this->file = $args[2];
        $this->line = $args[3];
        $this->context = $args[4];
        return $this; // chainable
    }

    public function fromException($e) {
        $this->message = $e->getMessage();
        return $this; // chainable
    }

    public function fromLastError($error)
    {
        $this->level = $error['type'];
        $this->message = $error['message'];
        $this->file = $error['file'];
        $this->line = $error['line'];
        return $this; // chainable
    }
} // end class Exception
