<?php
namespace Platform;

use \DateTime;

class Filter {

    /**
     * @param string $str
     * @param int $limit
     * @param bool $abbreviate
     * @return string
     */
    public static function truncate($str, $limit=50, $abbreviate=false)
    {

        if (strlen($str) <= $limit) {
            return $str;
        }

        if ($abbreviate) {
            $str = '<abbr title="'.$str.'">'.trim(substr($str, 0, $limit-3)).'...</abbr>';
        } else {
            $str = trim(substr($str, 0, $limit-3)).'...';
        }

        return $str;

    }

    /**
     * @param string $str
     * @param int $min
     * @param int $max
     * @return string
     */
    public static function summarise($str, $min=140, $max=150)
    {

        $str = strip_tags($str);

        if (empty($str)) {
            return false;
        }

        $sect_1 = substr($str, 0, $min);
        $sect_2 = substr($str, $min, $max-$min);

        $excess = strstr($sect_2, ".");

        if ($excess) {
            $sect_2 = str_replace($excess, ".", $sect_2);
            return $sect_1.$sect_2;
        }

        $rpos = strrpos($sect_2, " ");
        $sect_2 = substr($sect_2, 0, $rpos);
        return $sect_1.$sect_2.'...';

    }

    /**
     * @param int $count
     * @param string $singular
     * @param string $plural
     * @return string
     */
    public static function pluralise($count, $singular, $plural=false)
    {

        if (!$plural) {
            $plural = $singular.'s';
        }

        if ($count == 1) {
            return $singular;
        } else {
            return $plural;
        }

    }

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
     * @param int $secs
     * @return string
     */
    public static function humanTime($secs)
    {

        $lang = array();

        //english
        $lang[1]['SECONDS'] = 'seconds';
        $lang[1]['MINUTE'] = 'minute';
        $lang[1]['MINS'] = 'mins';
        $lang[1]['HOUR'] = 'hour';
        $lang[1]['HOURS'] = 'hours';
        $lang[1]['DAY'] = 'day';
        $lang[1]['DAYS'] = 'days';
        $lang[1]['WEEK'] = 'week';
        $lang[1]['WEEKS'] = 'weeks';
        $lang[1]['MONTH'] = 'month';
        $lang[1]['MONTHS'] = 'months';
        $lang[1]['YEAR'] = 'year';
        $lang[1]['YEARS'] = 'years';

        //welsh
        $lang[2]['SECONDS'] = 'eiliadau';
        $lang[2]['MINUTE'] = 'munud';
        $lang[2]['MINS'] = 'm\'dau';
        $lang[2]['HOUR'] = 'awr';
        $lang[2]['HOURS'] = 'awr';
        $lang[2]['DAY'] = 'ddiwrnod';
        $lang[2]['DAYS'] = 'diwrnod';
        $lang[2]['WEEK'] = 'wythnos';
        $lang[2]['WEEKS'] = 'wythnosau';
        $lang[2]['MONTH'] = 'mis';
        $lang[2]['MONTHS'] = 'misau';
        $lang[2]['YEAR'] = 'blwyddyn';
        $lang[2]['YEARS'] = 'blynyddau';

        if (!isset($GLOBALS['lang_id'])) {
            $GLOBALS['lang_id'] = 1;
        }

        if ($secs < 60) {

            return $lang[$GLOBALS['lang_id']]['SECONDS'];

        } else if ($secs <= 60 * 60) {

            $mins = round($secs / 60);

            if ($mins <= 1) {
                return '1 '.$lang[$GLOBALS['lang_id']]['MINUTE'];
            } else {
                return sprintf('%s '.$lang[$GLOBALS['lang_id']]['MINS'], $mins);
            }

        } else if ($secs <= 60 * 60 * 24) {

            $hours = round($secs / 60 / 60);

            if ($hours <= 1) {
                return '1 '.$lang[$GLOBALS['lang_id']]['HOUR'];
            } else {
                return sprintf('%s '.$lang[$GLOBALS['lang_id']]['HOURS'], $hours);
            }

        } else if ($secs <= 60 * 60 * 24 * 14) {

            $days = round($secs / 60 / 60 / 24);

            if ($GLOBALS['lang_id'] == 2) {

                if ($days <= 1) {
                    return '1 '.$lang[2]['DAYS'];
                } elseif ($days == 2) {
                    return '2 '.$lang[2]['DAY'];
                } else {
                    return sprintf('%s '.$lang[2]['DAYS'], $days);
                }

            } else {

                if ($days <= 1) {
                    return '1 '.$lang[$GLOBALS['lang_id']]['DAY'];
                } else {
                    return sprintf('%s '.$lang[$GLOBALS['lang_id']]['DAYS'], $days);
                }

            }

        } else if ($secs <= 60 * 60 * 24 * 7 * 10) {

            $weeks = round($secs / 60 / 60 / 24 / 7);

            if ($weeks <= 1) {
                return '1 '.$lang[$GLOBALS['lang_id']]['WEEK'];
            } else {
                return sprintf('%s '.$lang[$GLOBALS['lang_id']]['WEEKS'], $weeks);
            }

        } else if ($secs <= 60 * 60 * 24 * 30 * 24) {

            $months = round($secs / 60 / 60 / 24 / 30);

            if ($months <= 1) {
                return '1 '.$lang[$GLOBALS['lang_id']]['MONTH'];
            } else {
                return sprintf('%s '.$lang[$GLOBALS['lang_id']]['MONTHS'], $months);
            }

        } else {

            $years = round($secs / 60 / 60 / 24 / 365);

            if ($years <= 1) {
                return '1 '.$lang[$GLOBALS['lang_id']]['YEAR'];
            } else {
                return sprintf('%s '.$lang[$GLOBALS['lang_id']]['YEARS'], $years);
            }

        }

    }

    /**
     * @param int $from
     * @param int $to
     * @return string
     */
    public static function timeAgo($from, $to = false)
    {
        if (!$to) {
            $to = time();
        }

        $diff = abs($to - $from);

        return self::humanTime($diff);
    }

    /**
     * @param string $time
     * @return int
     */
    public static function timeToSecs($time)
    {

        if (!$time) {
            return 0;
        }

        $time_split = explode(':', $time);
        $hours = intval($time_split[0]);
        $mins = intval($time_split[1]);

        if (isset($time_split[2])) {
            $secs = intval($time_split[2]);
        } else {
            $secs = 0;
        }

        $rtn = 0;
        $rtn += $hours * 60 * 60;
        $rtn += $mins * 60;
        $rtn += $secs;

        return $rtn;

    }

    /**
     * @param string $url
     * @return string
     */
    public static function httpUrl($url)
    {
        $url = 'http://'.$url;
        $url = str_replace("http://http", "http", $url);
        return $url;
    }

    /**
     * @param string $url
     * @return string
     */
    public static function httpStrip($url)
    {
        $url = str_replace("http://", "", $url);
        $url = str_replace("https://", "", $url);
        return $url;
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
                $rtn = array_merge($rtn, Filter::array_flatkeys($children));
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

    /**
     * @param string $str
     * @return DateTime
     */
    public static function fromMysqlTime(&$str)
    {

        if ($str == '0000-00-00 00:00:00' || !$str) {
            $str = false;
        } else {
            $str = new DateTime($str);
        }

        return $str;

    }

    /**
     * @param int $time
     * @return string
     */
    public static function fromUnixTime(&$time)
    {
        $time = intval($time);
        $time = new DateTime('@'.$time);
        return $time;
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
     * @param mixed $date
     * @return string
     */
    public static function mysqltime(&$date)
    {
        if (!isset($date)) {
            $date = null;
        } elseif (!$date) {
            $date = false;
        } elseif (is_numeric($date)) {
            $date = date('Y-m-d H:i:s', $date);
        } elseif (is_a($date, 'DateTime')) {
            $date = $date->format('Y-m-d H:i:s');
        }

        return $date;
    }

    /**
     * @param mixed $date
     * @return DateTime
     */
    public static function datetime(&$date)
    {
        if (!isset($date)) {
            $date = null;
        } elseif (is_a($date, 'DateTime')) {
            //do nothing if already a datetime
        } elseif (is_numeric($date)) {
            $unixtime = $date;
            $date = new DateTime();
            $date->setTimestamp($unixtime);
        } elseif ($date) {

            if ($date == '0000-00-00 00:00:00') {
                return false;
            } elseif ($date == '0000-00-00') {
                return false;
            }

            $date = str_replace('/', '.', $date);
            $date = new DateTime($date);
        }

        return $date;
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
     * @param string $str
     * @return string
     * @todo
     */
    public static function purify($str)
    {
        return $str;
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
     * @deprecated
     * @param int $secs
     * @return string
     */
    public static function human_time($secs)
    {
        return self::humanTime($secs);
    }

    /**
     * @deprecated
     * @param int $from
     * @param int $to
     * @return string
     */
    public static function time_ago($from, $to = false)
    {
        return self::timeAgo($from, $to);
    }

    /**
     * @deprecated
     * @param string $time
     * @return int
     */
    public static function time_to_secs($time)
    {
        return self::timeToSecs($time);
    }

    /**
     * @deprecated
     * @param string $url
     * @return string
     */
    public static function http_url($url)
    {
        return self::httpUrl($url);
    }

    /**
     * @deprecated
     * @param string $url
     * @return string
     */
    public static function http_strip($url)
    {
        return self::httpStrip($url);
    }

    /**
     * @deprecated
     * @param array $results_array
     * @param string $key_to_use
     * @param string $label_to_use
     * @return array
     */
    public static function array_simplify($results_array, $key_to_use='id', $label_to_use='name')
    {
        return self::arraySimplify($results_array, $key_to_use, $label_to_use);
    }

    /**
     * @deprecated
     * @param array $values
     * @param array $fields
     * @return array
     */
    public static function array_condense($values, $fields)
    {
        return self::arrayCondense($values, $fields);
    }

    /**
     * @deprecated
     * @param array $array
     * @return array
     */
    public static function array_flatkeys($array)
    {
        return self::arrayFlatkeys($array);
    }

    /**
     * @deprecated
     * @param array $array
     * @param string $key
     * @return array
     */
    public static function array_rearrange($array, $key)
    {
        return self::arrayRearrange($array, $key);
    }

    /**
     * @deprecated
     * @param array $array
     * @return array
     */
    public static function array_shuffle($array)
    {
        return self::arrayShuffle($array);
    }

    /**
     * @deprecated
     * @param string $str
     * @return DateTime
     */
    public static function from_mysqltime(&$str)
    {
        return self::fromMysqlTime($str);
    }

    /**
     * @deprecated
     * @param int $time
     * @return string
     */
    public static function from_unixtime(&$time)
    {
        return self::fromUnixTime($time);
    }

}
