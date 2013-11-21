<?php

namespace SinsScherzo;

class ErrorController extends Controller
{

    protected $e;

    public function invoke()
    {
        if ($this->response === null) {
            $this->response = new Response($this->app);
        }
        $status = $this->e->status;

        $this->response->body = "Error {$this->e->status}" . $this->e->getMessage();
        $this->response->status = $this->e->status;
    }





    public function setException(\Exception $e)
    {
        if (is_a(__NAMESPACE__.'\Exception', $e)) {
            $this->e = $e;
        } else {
            $this->e = (new Exception)->fromException($e);
        }
    }
}
