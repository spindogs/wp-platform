<?php
namespace Platform;

use stdClass;

class Router {

    protected $rules;

    /**
     * @param string $regex
     * @param string $controller
     * @param string $action
     * @param string $method
     */
    public function add($regex, $controller, $action, $method = 'ALL')
    {
        $method = strtoupper($method);

        $route = new stdClass();
        $route->regex = $regex;
        $route->controller = $controller;
        $route->action = $action;

        $this->rules[$method][] = $route;
    }

    /**
     * @param string $regex
     * @param string $controller
     * @param string $action
     * @return void
     */
    public function get($regex, $controller, $action)
    {
        $this->add($regex, $controller, $action, 'GET');
    }

    /**
     * @param string $regex
     * @param string $controller
     * @param string $action
     * @return void
     */
    public function post($regex, $controller, $action)
    {
        $this->add($regex, $controller, $action, 'POST');
    }

    /**
     * @return void
     */
    public function dispatch()
    {
        //standardise uri
        $uri = Request::getPath();
        $uri = trim($uri, '/');
        // $uri = strtolower($uri);

        //request method
        $method = Request::getMethod();

        //see if the url matches any rules
        $route = $this->getRoute($uri, $method);

        if (!$route) {
            $route = $this->getRoute($uri, 'ALL');
        }

        if (!$route) {
            return;
        }

        $num_params = count($route->params);
        $controller_name = $route->controller;

        if (!class_exists($controller_name)) {
            $controller_name = 'App\\Controller\\'.$controller_name;
        }

        $controller = new $controller_name();
        $controller->call($route->action, $route->params);
        exit;
    }

    /**
     * @param string $needle
     * @param string $method
     * @return StdClass
     */
    public function getRoute($needle, $method)
    {
        $matches = array();

        if (empty($this->rules[$method])) {
            return false;
        }

        foreach ($this->rules[$method] as $route) {

            $regex = $route->regex;
            $regex = ltrim($regex, '^');
            $regex = rtrim($regex, '$');
            $regex = str_replace('/', '\/', $regex);
            $regex = '/^'.$regex.'$/';

            if (preg_match($regex, $needle, $matches)) {
                break;
            }

        }

        if (!$matches) {
            return false;
        }

        // print_r($matches);exit;

        $params = $matches;
        unset($params[0]);
        $params = array_values($params);
        $first_char = substr($route->action, 0, 1);

        if ($first_char == '$') {
            $i = $route->action;
            $i = ltrim($i, '$');
            $i--; //allow for index base 0
            $route->action = $params[$i];
            unset($params[$i]);
            $params = array_values($params); //reset indexes
        }

        $route->params = $params;
        // print_r($route);exit;
        return $route;
    }

}
