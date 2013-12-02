<?php

namespace xSins\Controller;

use Exception;

class ErrorController extends \Sins\Controller {

    public function setException(Exception $e) {
        $this->e = $e;
        return $this; // chainable
    }

    public function execute($id = null, $action = null) {
        // we may not even have a request yet
        if ($this->response === null) {
            $this->response = \Sins\Response::factory($this->request);
            $this->response->type = 'text';
        }
        $this->response->status = $this->e->status;
        $this->getPage();
    }

    protected function getApi($id = null) {
    }

    protected function postApi($id = null) {
    }

    protected function getPage($id = null) {
        $view = new \Sins\PhpView;
        $view->e = $this->e;
        if ($this->app->shared('local')->runmode === 'development' && $this->e->status === 500) {
            // show debug page
            try {
                $view->setTemplate('error/error-debug');
            } catch (\Exception $e) {
                $this->response->type = 'text';
                $this->response->body = "(Could not load debug template)\n" . (string)$this->e;
                return;
            }
        } else {
            try {
                // try e.g. error-404
                $status = $this->response->status;
                $view->setTemplate("error/error-$status");
            } catch (Exception $e) {
                try {
                    // try e.g. error-4xx
                    $status = substr($status, 1, 1) . 'xx';
                    $view->setTemplate("error/error-$status");
                } catch (Exception $e) {
                    try {
                        // try error-default
                        $view->setTemplate("error/error-default");
                    } catch (Exception $e) {
                        $this->response->body = 'No error templates: Error ' . $this->response->status;
                        return;
                    }
                }
            }
        }
        $this->response->body = $view->render();
    }

    protected function postForm($id = null) {
    }

}
