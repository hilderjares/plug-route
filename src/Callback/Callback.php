<?php

namespace PlugRoute\Callback;

use PlugRoute\Error;
use PlugRoute\Helpers\ValidateHelper;
use PlugRoute\Http\Request;
use PlugRoute\Http\Response;
use PlugRoute\Middleware\PlugRouteMiddleware;

class Callback
{
    private $request;

    private $response;

    public function __construct($name)
    {
        $this->request 	= new Request($name);
        $this->response = new Response();
    }

    public function handleCallback($route, array $urlParameters = [])
    {
		$this->request->setUrlParameter($urlParameters);

		if (!empty($route['middleware'])) {
			$this->callMiddleware($route['middleware']);
		}

		if (is_callable($route['callback'])) {
            return $this->callFunction($route['callback']);
        }

        return $this->handleObject($route);
    }

    private function callMiddleware($middlewares)
    {
        foreach ($middlewares as $middleware) {
            $obj = new $middleware();

            if (!($obj instanceof PlugRouteMiddleware)) {
            	Error::showError('Error: your class should implement PlugRouteMiddleware');
            }

            $this->request = $obj->handle($this->request);
        }
    }

    private function handleObject($route)
    {
        $callback = explode("@", $route['callback']);
        $instance = $this->createObject($callback[0]);
        return $this->callMethod($instance, $callback[1]);
    }

    private function createObject($class)
    {
        if (ValidateHelper::classExist($class)) {
			return new $class;
		}

		Error::showError("Error: class don't exist.");
    }

    private function callMethod($instance, $method)
    {
        if (ValidateHelper::methodExist($instance, $method)) {
            return $instance->$method($this->request, $this->response);
        }

		Error::showError("Error: method don't exist.");
    }

    private function callFunction($function)
    {
        return $function($this->request, $this->response);
    }
}