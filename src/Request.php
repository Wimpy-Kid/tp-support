<?php


namespace CherryLu\TpSupport;

class Request extends \think\Request {

    public function __set($name, $value) {
        $this->$name = $value;
    }

    public function __get(string $name) {
        return $this->param($name);
    }

    public function setMiddlewareParam($name, $value) {
        $this->middleware[$name] = $value;
    }

    public function getMiddlewareParam($name) {
        return $this->middleware($name);
    }

}
