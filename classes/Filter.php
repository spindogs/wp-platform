<?php
namespace Platform;

use DateTime;

class Filter {

    /**
     * @param string $str
     * @return string
     */
    public static function clean($str)
    {
        $trans = array(
            ' ' => '-',
            '.' => '-',
            '?' => '-',
            '!' => '-',
            '_' => '-',
            '(' => '-',
            ')' => '-',
            ',' => '-',
            '&' => 'and'
        );

        $str = trim($str);
        $str = strtolower($str);
        $str = strtr($str, $trans);
        $str = preg_replace('/[^a-z0-9-]/', '', $str);
        $str = trim($str, '-');

        while (strpos($str, '--') !== false) {
            $str = str_replace('--', '-', $str);
        }

        return $str;
    }

    /**
     * @param mixed $bool
     * @return string
     */
    public static function yesno($bool)
    {
        if ($bool) {
            return 'Yes';
        } else {
            return 'No';
        }
    }

    /**
     * @param string $str
     * @return void
     */
    public static function unconcat(&$str)
    {

        if (is_array($str)) {
            unset($str);
            return false;
        }

        if (!empty($str)) {
            $str = explode(',', $str);
        } else {
            $str = array();
        }

        unset($str);

    }

    /**
     * @param string $filename
     * @return string
     */
    public static function extension($filename)
    {
        $rtn = trim($filename);
        $rtn = strrchr($rtn, '.');
        $rtn = strtolower($rtn);
        $rtn = ltrim($rtn, '.');
        return $rtn;
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @return string
     */
    public static function remove($haystack, $needle)
    {
        $rtn = str_replace($needle, '', $haystack);
        return $rtn;
    }

    /**
     * @param mixed $str
     * @return int|float
     */
    public static function numeric($str)
    {

        if (strpos($str, '.') !== false) {
            return floatval($str);
        } else {
            return intval($str);
        }

    }

    /**
     * @param mixed $var
     * @return mixed|null
     */
    public static function nullify(&$var)
    {
        if (isset($var)) {
            return $var;
        } else {
            return NULL;
        }

    }

    /**
     * @param multiple
     * @return mixed
     */
    public static function coalesce()
    {
        $args = func_get_args();

        foreach ($args as $arg) {

            if ($arg !== NULL) {
                return $arg;
            }

        }

    }

    /**
     * @param string $str
     * @return string
     */
    public static function titleCase($str)
    {
        $str = strtolower($str);
        $str = ucwords($str);
        $str = str_replace('_', ' ', $str);
        return $str;
    }

    /**
     * @param string $str
     * @return string
     */
    public static function snakeCase($str)
    {
        $str = preg_replace('/(?<!^)[A-Z]/', '_$0', $str);
        $str = strtolower($str);
        return $str;
    }

    /**
     * @param array $results_array
     * @param string $key_to_use
     * @param string $label_to_use
     * @return array
     */
    public static function arraySimplify($results_array, $key_to_use='id', $label_to_use='name')
    {

        $results_array = (array)$results_array;
        $rtn = array();

        foreach ($results_array as $r) {
            $r = (array)$r;
            if ($key_to_use === NULL) {
                $rtn[] = $r[$label_to_use];
            } else {
                $rtn[$r[$key_to_use]] = $r[$label_to_use];
            }
        }

        return $rtn;

    }

    /**
     * @param array $values
     * @param array $fields
     * @return array
     */
    public static function arrayCondense($values, $fields)
    {

        $values = (array)$values;
        $rtn = array();

        foreach ($fields as $key => $new_key) {

            if (is_int($key)) {
                $key = $new_key;
            }

            if (isset($values[$key])) {
                $rtn[$key] = $values[$key];
            }

        }

        return $rtn;

    }

    /**
     * @param array $array
     * @return array
     */
    public static function arrayFlatkeys($array)
    {

        $rtn = array();

        foreach ($array as $key => $children) {

            $rtn[] = $key;

            if (is_array($children)) {
                $rtn = array_merge($rtn, self::arrayFlatkeys($children));
            }

        }

        return $rtn;

    }

    /**
     * @param array $array
     * @param string $key
     * @return array
     */
    public static function arrayRearrange($array, $key)
    {

        $rtn = array();

        foreach ($array as $r) {
            $obj = (object)$r;
            $rtn[$obj->{$key}] = $r;
        }

        return $rtn;

    }

    /**
     * @param array $array
     * @return array
     */
    public static function arrayShuffle($array)
    {
        $keys = array_keys($array);
        $rtn = array();

        shuffle($keys);

        foreach($keys as $key) {
            $rtn[$key] = $array[$key];
        }

        return $rtn;
    }

}
