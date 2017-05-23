<?php
namespace Platform;

use Platform\Filter;

class Request {

    protected static $is_setup = false;
    protected static $method;
    protected static $scheme;
    protected static $host;
    protected static $path;
    protected static $query;
    protected static $fragment;

    /**
     * @return void
     */
    public static function setup()
    {
        //check if already setup
        if (self::$is_setup) {
            return;
        }

        //method
        if (empty($_SERVER['REQUEST_METHOD'])) {
            self::$method = 'GET';
        } else {
            self::$method = $_SERVER['REQUEST_METHOD'];
        }

        //scheme
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
            self::$scheme = 'https://';
        } else {
            self::$scheme = 'http://';
        }

        //host
        if (isset($_SERVER['HTTP_HOST'])) {
            self::$host = $_SERVER['HTTP_HOST'];
        } else {
            self::$host = '';
        }

        //path + query
        if (isset($_SERVER['REQUEST_URI'])) {

            if (strpos($_SERVER['REQUEST_URI'], '?') !== false) {
                self::$path = strstr($_SERVER['REQUEST_URI'], '?', true);
            } else {
                self::$path = $_SERVER['REQUEST_URI'];
            }

            self::$query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);

        } else {
            self::$path = '';
            self::$query = '';
        }

    }

    /**
     * @param string $key
     * @param mixed $val
     * @param string $base_uri
     */
    public static function addVar($key, $val, $base_uri = null)
    {
        if (is_array($key)) {
            $vars = $key;
            $base_uri = $val;
        } else {
            $vars = array($key => $val);
        }

        if ($base_uri) {
            $base_uri = str_replace('&amp;', '&', $base_uri);
            $url_split = parse_url($base_uri);
            $base_uri = $url_split['path'];
            $query_str = @$url_split['query'];
        } else {
            $base_uri = self::getPath();
            $query_str = self::getQuery(false);
        }

        $query_vars = array();
        parse_str($query_str, $query_vars);

        foreach ($vars as $key => $val) {

            if ($val !== null) {
                $query_vars[$key] = $val;
            }

        }

        if ($query_vars) {
            return $base_uri.'?'.http_build_query($query_vars, '', '&');
        } else {
            return $base_uri;
        }

    }

    /**
     * @param string $key
     * @param string $base_uri
     * @return string
     */
    public static function removeVar($key, $base_uri = null)
    {
        if (is_array($key)) {
            $vars = $key;
        } else {
            $vars = array($key);
        }

        if ($base_uri) {
            $base_uri = str_replace('&amp;', '&', $base_uri);
            $url_split = parse_url($base_uri);
            $base_uri = $url_split['path'];
            $query_str = @$url_split['query'];
        } else {
            $base_uri = self::getPath();
            $query_str = self::getQuery(false);
        }

        $query_vars = array();
        parse_str($query_str, $query_vars);

        foreach ($vars as $key) {

            if (isset($query_vars[$key])) {
                unset($query_vars[$key]);
            }

        }

        if ($query_vars) {
            return $base_uri.'?'.http_build_query($query_vars, '', '&');
        } else {
            return $base_uri;
        }
    }

    /**
     * @return string
     */
    public static function getMethod()
    {
        self::setup();
        return self::$method;
    }

    /**
     * @return string
     */
    public static function getScheme()
    {
        self::setup();
        return self::$scheme;
    }

    /**
     * @return string
     */
    public static function getHost()
    {
        self::setup();
        return self::$host;
    }

    /**
     * @return string
     */
    public static function getPath()
    {
        self::setup();
        return self::$path;
    }

    /**
     * @param boolean $with_prefix
     * @return string
     */
    public static function getQuery($with_prefix = true)
    {
        self::setup();

        if (!self::$query) {
            return '';
        } elseif ($with_prefix) {
            return '?'.self::$query;
        } else {
            return self::$query;
        }
    }

    /**
     * @return string
     */
    public static function getFragment()
    {
        self::setup();
        return self::$fragment;
    }

    /**
     * @return string
     */
    public static function getHttpUrl()
    {
        self::setup();
        return self::getScheme().self::getHost();
    }

    /**
     * @deprecated
     * @return string
     */
    public static function getUri()
    {
        self::setup();
        return self::getRequest();
    }

    /**
    * @return string
    */
    public static function getRequest()
    {
        self::setup();
        return self::getPath().self::getQuery();
    }

    /**
     * @return string
     */
    public static function getUrl()
    {
        self::setup();
        return self::getScheme().self::getHost().self::getPath().self::getQuery();
    }

    /**
     * @param string $key
     * @return mixed
     */
    public static function getParam($key)
    {
        self::setup();
        $rtn = Filter::nullify($_GET[$key]);
        return $rtn;
    }

}
