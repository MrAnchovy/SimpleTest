<?php

namespace Sins\Controller;

class DefaultController extends \SinsScherzo\Controller
{
    public function invoke()
    {
//        throw new \Exception('Ordinary Exception');
//        dfdssdf();
        $this->response->body = 'This is the Sins controller';
    }
}
