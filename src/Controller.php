<?php


namespace cherrylu\TpSupport;

use think\App;

class Controller extends \app\BaseController {

    /**
     * Request实例
     * @var Request
     */
    protected $request;
    /**
     * @var Response
     */
    protected $response;

    /** @var array | bool */
    protected $withoutMiddleware;
    protected $middlewareIgnoreFunctions;

    public function __construct(App $app, Request $request, Response $response) {
        parent::__construct($app);
        $this->request  = $request;
        $this->response = $response;
        if ( true === $this->withoutMiddleware ) {
            $this->middleware = [];
        } else {
            foreach ( $this->withoutMiddleware ?? [] as $middleware ) {
                $key = array_search($middleware, $this->middleware);
                if ( $key !== false ) {
                    unset($this->middleware[$key]);
                }
            }
        }

        if ( $this->middlewareIgnoreFunctions && in_array(request()->action(), $this->middlewareIgnoreFunctions) ) {
            $this->middleware = [];
        }
    }


}
