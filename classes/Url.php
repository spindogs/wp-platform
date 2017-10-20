<?php
namespace Platform;

use Platform\Filter;
use Platform\Request;
use Platform\Setup;
use Platform\Exception;

class Url {

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
            $base_uri = Request::path();
            $query_str = Request::query(false);
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
            $base_uri = Request::path();
            $query_str = Request::query(false);
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
     * @param string $url
     * @return string
     */
    public static function http($url)
    {
        $url = 'http://'.$url;
        $url = str_replace('http://http', 'http', $url);
        return $url;
    }

    /**
     * @param string $url
     * @return string
     */
    public static function stripHttp($url)
    {
        $url = str_replace('http://', '', $url);
        $url = str_replace('https://', '', $url);
        return $url;
    }

    /**
     * @param string $url
     * @return string
     * @throws Exception
     */
    public static function tokenise($url)
    {
        if (Setup::salt()) {
            $salt = Setup::salt();
        } else {
            throw new Exception('No security salt is set');
        }

        $url = str_replace('&amp;', '&', $url);
        $url = parse_url($url);

        $path = Filter::nullify($url['path']);
        $query = Filter::nullify($url['query']);
        $fragment = Filter::nullify($url['fragment']);

        if (!$path) {
            $path = Request::path();
        }
        if ($query) {
            $query = '?'.urldecode($query);
        }
        if ($fragment) {
            $fragment = '#'.$fragment;
        }

        $new_url = $path.$query;
        $token = sha1($new_url.$salt);

        if ($query) {
            return $new_url.'&token='.$token.$fragment;
        } else {
            return $new_url.'?token='.$token.$fragment;
        }
    }

    /**
     * @return bool
     */
    public static function checkToken()
    {
        if (Setup::salt()) {
            $salt = Setup::salt();
        } else {
            return;
        }

        if (empty($_GET['token'])) {
            die('This link is broken, please ignore it. We apologise for the inconvenience');
        }

        $url = Request::path();

        $query = Request::query(false);
        $query = str_replace('&amp;', '&', $query);
        parse_str($query, $vars);
        unset($vars['token']);
        $query = http_build_query($vars, '', '&');
        $query = urldecode($query);

        if ($query) {
            $url .= '?'.$query;
        }

        $key = sha1($url.$salt);
        $token = $_GET['token'];

        if ($key != $token) {
            die('This link is broken, please ignore it. We apologise for the inconvenience');
        }
    }

}
