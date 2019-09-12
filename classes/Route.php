<?php
namespace Platform;

use Platform\Request;
use Platform\Filter;

class Route
{
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
        $admin_prefix = 'wp-admin';

        $regex = ltrim($regex, '/');
        $regex = $admin_prefix.'/'.$regex;
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

        $network_url = network_site_url();
        $network_url_length = strlen($network_url);
        $blog_id = get_current_blog_id();
        $blog_url = get_site_url($blog_id);
        $blog_url_portion = substr($blog_url, 0, $network_url_length);

        $uri = $path_to_page;
        $uri = trim($uri, '/');
        $uri = strtolower($uri);

        if ($blog_url_portion == $network_url) {
            //multisite blogs use suffixes
            $uri = $network_url.$uri; //add in network url
            $uri = str_replace($blog_url, '', $uri); //remove blog url
        } else {
            //multisite blogs use TLDN
        }

        $uri = trim($uri, '/');
        $rules = static::$rules;

        // print_r($network_url);exit;
        // print_r($blog_url);exit;
        // print_r($uri);exit;
        // print_r($rules);exit;

        //see if the url matches any rules
        $matches = array();

        foreach ($rules as $route) {

            $rule = $route->regex;
            $rule = trim($rule, '/');
            $rule = trim($rule, '^');
            $rule = trim($rule, '$');
            $rule = str_replace('/', '\/', $rule);
            $rule = '/^'.$rule.'$/i';

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
            $GLOBALS['_wp_submenu_nopriv'] = [];
            $wp_db_version = get_option('db_version');
            $_POST['stop_wp_upgrade_message'] = true;
            require(ABSPATH.'/wp-admin/admin.php');
        }

        $action = $route->action;
        $params = $route->params;
        $controller_name = $route->controller;

        if (!class_exists($controller_name)) {
            $controller_name = 'App\\Controller\\'.$controller_name;
        }

        $controller = new $controller_name();
        $controller->call($action, $params);
        exit;
    }

}
