<?php

namespace Sins\Controller;

class DefaultController extends \Sins\Controller {

    function executeGetIndex() {
        $this->response->body = 'This is the Index Page for the default controller; it should be overriden by your application.';
    }

}
