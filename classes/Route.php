<?php
namespace Platform;

use Platform\Request;
use Platform\Filter;

class Route {

    protected static $rules = [];
    protected static $admin_rules = [];
    protected static $current_route;

    protected $regex;
    protected $controller;
    protected $action;
    protected $params;
    protected $is_admin;

    /**
    * @return void
    */
    public function call($controller, $action)
    {
        $this->controller = $controller;
        $this->action = $action;
        self::$rules[] = $this;
    }

    /**
     * @return Route
     */
    public static function match($regex)
    {
        $route = new self();
        $route->regex = $regex;
        $route->is_admin = false;
        return $route;
    }

    /**
     * @return Route
     */
    public static function admin($regex)
    {
        $regex = ltrim($regex, '/');
        $regex = 'wp-admin/'.$regex;
        $route = new self();
        $route->regex = $regex;
        $route->is_admin = true;
        return $route;
    }

    /**
     * @return void
     */
    public static function dispatch()
    {
        //get request path
        $path_to_page = Request::path();

        if (!static::$rules) {
            return; //no routes to match
        }

        $uri = $path_to_page;
        $uri = trim($uri, '/');
        $uri = strtolower($uri);
        $rules = static::$rules;

        //see if the url matches any rules
        $matches = array();

        foreach ($rules as $route) {

            $rule = $route->regex;
            $rule = trim($rule, '/');
            $rule = trim($rule, '^');
            $rule = trim($rule, '$');
            $rule = str_replace('/', '\/', $rule);
            $rule = '/^'.$rule.'$/';

            if (preg_match($rule, $uri, $matches)) {
                break;
            }

        }

        //print_r($matches);exit;
        //print_r($route);exit;

        if (!$matches) {
            return;
        }

        $params = $matches;
        unset($params[0]);
        $params = array_values($params);

        $route->params = $params;
        self::$current_route = $route;

        self::dispatchToController();
    }

    /**
     * @return void
     */
    public static function dispatchToController()
    {
        $route = self::$current_route;

        if ($route->is_admin) {
            global $menu;
            $path_to_page = Request::path();
            $uri = $path_to_page;
            $uri = trim($uri, '/');
            $uri = strtolower($uri);
            $uri = str_replace('wp-admin/', '', $uri);
            $uri_split = explode('/', $uri);
            $parent_file = reset($uri_split);
            $GLOBALS['parent_file'] = $parent_file;
            $_POST['stop_wp_upgrade_message'] = true;
            require(ABSPATH.'/wp-admin/admin.php');
        }

        $action = $route->action;
        $params = $route->params;
        $controller_name = $route->controller;
        $controller_name = 'App\\Controller\\'.$controller_name;
        $controller = new $controller_name();
        $controller->call($action, $params);
        exit;
    }

}
