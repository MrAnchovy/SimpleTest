<?php

namespace Sins\Controller;

class Controller_run extends \Sins\Controller {

    function executeApi($id = null) {
        $this->response->body = array(
            'status' => 'ok',
            'msg' => strtr('This is the api controller test action called with id :id; it should be overriden by your application.', array(':id' => $id)),
        );
    }
}
