<?php

namespace Sins\Controller;

class DefaultController extends \Sins\Controller {

    function executeGetIndex() {
        $page = new \Sins\PhpView;
        $page->assetsPath = $this->local->assetsPath;
        $page->setTemplate(__DIR__.'/../../../templates/Sins/sins-page.tpl.php');
        $this->response->body = $page->render();
    }

}
