<?php
namespace Platform;

use oDatabase;

class Security {

    /**
     * @param string $str
     * @return string
     */
    public static function escHtml($str)
    {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param string $str
     * @return string
     */
    public static function escSql($str)
    {
        if (function_exists('esc_sql')) {
            return '\''.esc_sql($str).'\'';
        }

        if (isset($GLOBALS['Database'])) {
            $database = $GLOBALS['Database'];
        } else {
            $database = new oDatabase();
        }

        $rtn = mysqli_real_escape_string($database->link, $str);
        return '\''.$rtn.'\'';
    }

    /**
     * @param string $asc_desc
     * @return string
     */
    public static function escAsc($asc_desc)
    {
        $asc_desc = strtoupper($asc_desc);

        if ($asc_desc == 'ASC' || $asc_desc == 'DESC') {
            $rtn = $asc_desc;
        } else {
            $rtn = '';
        }

        return $rtn;
    }

    /**
    * @param string $q
    * @return string
    */
    public static function escCol($q)
    {
        $q = str_replace('`', '', $q);
        $bits = explode('.', $q);
        $rtn = implode('`.`', $bits);
        $rtn = '`'.$rtn.'`';
        return $rtn;
    }

    /**
     * @param array|string $vars
     * @return string
     */
    public static function escCsv($vars)
    {
        if (!$vars) {
            return '\'X=X\''; //this prevents empty arrays causing an sql error
        }

        $rtn = '';

        if (!is_array($vars)) {
            $vars = explode(',', $vars);
        }

        foreach ($vars as $var) {
            $rtn .= self::escSql($var).',';
        }

        return rtrim($rtn, ',');
    }

    /**
     * @deprecated
     * @param array|string $vars
     * @return string
     */
    public static function escIns($vars)
    {
        return self::escCsv($vars);
    }

    /**
     * @param string $url
     * @return string
     */
    public static function makeToken($url)
    {
        if (Setup::$salt) {
            $salt = Setup::$salt;
        } else {
            return;
        }

        $url = str_replace('&amp;', '&', $url);
        $url = str_replace('&', '&amp;', $url);
        $url = parse_url($url);

        $path = Filter::nullify($url['path']);
        $query = Filter::nullify($url['query']);
        $fragment = Filter::nullify($url['fragment']);

        if (!$path) {
            $path = Request::getPath();
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
            return $new_url.'&amp;token='.$token.$fragment;
        } else {
            return $new_url.'?token='.$token.$fragment;
        }

    }

    /**
     * @return bool
     */
    public static function checkToken()
    {
        if (Setup::$salt) {
            $salt = Setup::$salt;
        } else {
            return;
        }

        if (empty($_GET['token'])) {
            die('This link is broken, please ignore it. We apologise for the inconvenience');
        }

        $url = Request::getPath();

        $query = Request::getQuery(false);
        $query = str_replace('&amp;', '&', $query);
        parse_str($query, $vars);
        unset($vars['token']);
        $query = http_build_query($vars, '', '&amp;');
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
