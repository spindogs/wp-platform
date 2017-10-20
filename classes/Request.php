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
     * @return string
     */
    public static function method()
    {
        self::setup();
        return self::$method;
    }

    /**
     * @return string
     */
    public static function scheme()
    {
        self::setup();
        return self::$scheme;
    }

    /**
     * @return string
     */
    public static function host()
    {
        self::setup();
        return self::$host;
    }

    /**
     * @return string
     */
    public static function path()
    {
        self::setup();
        return self::$path;
    }

    /**
     * @param boolean $with_prefix
     * @return string
     */
    public static function query($with_prefix = true)
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
    public static function fragment()
    {
        self::setup();
        return self::$fragment;
    }

    /**
     * @return string
     */
    public static function httpHost()
    {
        self::setup();
        return self::scheme().self::host();
    }

    /**
    * @return string
    */
    public static function get()
    {
        self::setup();
        return self::path().self::query();
    }

    /**
     * @return string
     */
    public static function url()
    {
        self::setup();
        return self::scheme().self::host().self::path().self::query();
    }

    /**
     * @param string $key
     * @return mixed
     */
    public static function param($key)
    {
        $rtn = Filter::nullify($_GET[$key]);
        return $rtn;
    }

}
