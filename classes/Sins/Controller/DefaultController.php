<?php

namespace Sins\Controller;

class DefaultController extends \Sins\Controller {

    function executeGetIndex() {
        $page = new \Sins\PhpView;
        $page->assetsUrl = $this->local->assetsUrl;
        $page->baseUrl = $this->local->baseUrl;
        $page->apiUrl = $this->local->apiUrl;
        $page->setTemplate(__DIR__.'/../../../templates/Sins/sins-page.tpl.php');
        $this->response->body = $page->render();
    }

}
