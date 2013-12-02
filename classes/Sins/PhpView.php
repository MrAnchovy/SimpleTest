<?php

namespace Sins;

abstract class View {

    protected static $_initials = array();
    protected $_vars = array();

    public function __construct($template = null, $vars = null) {
        if ($template !== null) {
            $this->setTemplate($template);
        }
        $this->_vars = self::$_initials;
        if ($vars !== null) {
            $this->set($vars);
        }
    }

    abstract public function setTemplate($name, $options = null);

    public function setInitial($name, $value = null) {
        if (is_array($name)) {
            self::$_initials = array_merge(self::$_initials, $name);
            $this->_vars = array_merge($this->_vars, $name);
        } else {
            self::$_initials[$name] = $value;
            $this->_vars[$name] = $value;
        }
    }

    public function unsetInitial($name) {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                unset(self::$_initials[$key]);
                unset($this->_vars[$key]);
            }
        } else {
            self::$_initials[$name] = $value;
            $this->_vars[$name] = $value;
        }
    }

    public function __set($name, $value) {
        $this->_vars[$name] = $value;
    }

    abstract public function render($vars = null);
}

class PHPView extends View {
    protected $_filename;

    public function setTemplate($name, $options = null) {
        $this->_filename = $name;
        if (!file_exists($name)) {
            throw new \Exception(strtr('Cannot find template :name', array(':name' => $name)));
        }
    }

    public function render($vars = null) {
        try {
            extract($this->_vars);
            include $this->_filename;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
