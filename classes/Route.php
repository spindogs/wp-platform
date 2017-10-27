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
    * @return Route
    */
    public function subpage($regex)
    {
        $regex = trim($regex, '/');
        $this->regex .= '/'.$regex;
        return $this;
    }

    /**
    * @return void
    */
    public function call($controller, $action)
    {
        $this->controller = $controller;
        $this->action = $action;

        if ($this->is_admin) {
            self::$admin_rules[] = $this;
        } else {
            self::$rules[] = $this;
        }
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

        //determine if page is admin
        if ($path_to_page == '/wp-admin/admin.php') {
            $is_admin = true;
        } else {
            $is_admin = false;
        }

        //standardise uri
        if ($is_admin) {

            if (!static::$admin_rules) {
                return; //no routes to match
            }
            if (empty($_GET['page'])) {
                return; //no admin uri to match
            }

            $uri = $_GET['page'];
            $uri .= '/';
            $uri .= Filter::nullify($_GET['subpage']);
            $uri = trim($uri, '/');
            $uri = strtolower($uri);
            $rules = static::$admin_rules;

        } else {

            if (!static::$rules) {
                return; //no routes to match
            }

            $uri = $path_to_page;
            $uri = trim($uri, '/');
            $uri = strtolower($uri);
            $rules = static::$rules;

        }

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

        if (!$matches) {
            return;
        }

        $params = $matches;
        unset($params[0]);
        $params = array_values($params);

        $route->params = $params;
        self::$current_route = $route;

        if ($is_admin) {
            add_submenu_page(null, null, null, 'publish_pages', $uri, ['Platform\Admin', 'router']);
            $uri_split = explode('/', $uri);
            $GLOBALS['plugin_page'] = reset($uri_split);
            add_action('admin_init', [__CLASS__, 'dispatchToController']);
        } else {
            self::dispatchToController();
        }

    }

    /**
     * @return void
     */
    public static function dispatchToController()
    {
        $route = self::$current_route;
        $action = $route->action;
        $params = $route->params;
        $controller_name = $route->controller;
        $controller_name = 'App\\Controller\\'.$controller_name;
        $controller = new $controller_name();
        $controller->call($action, $params);
        exit;
    }

}
