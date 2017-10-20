<?php
namespace Platform;

use DateTime;

class Date {

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

}
