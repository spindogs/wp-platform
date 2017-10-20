<?php
namespace Platform;

use oDatabase;

class Security {

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

}
