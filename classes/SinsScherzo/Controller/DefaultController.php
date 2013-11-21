<?php

namespace SinsScherzo\Controller;

class DefaultController extends \SinsScherzo\Controller
{
    public function invoke()
    {
        $this->response->body = 'This is the default controller: your application should override this.';
    }
}
