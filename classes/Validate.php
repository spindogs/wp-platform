<?php
namespace Platform;
use \DateTime;

class Validate {

    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function contains($haystack, $needle)
    {

        if (strpos($haystack, $needle) === false) {
            return false;
        } else {
            return true;
        }

    }

    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function startsWith($haystack, $needle)
    {

        $needle_length = strlen($haystack);
        $haystack_to_match = substr($haystack, 0, $needle_length);

        if ($haystack_to_match == $needle) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * @param string $datetime
     * @return bool
     */
    public static function datePast($datetime)
    {

        $now = new DateTime();

        if ($datetime < $now) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * @param string $datetime
     * @return bool
     */
    public static function dateFuture($datetime)
    {

        $now = new DateTime();

        if ($datetime > $now) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * @deprecated
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function starts_with($haystack, $needle)
    {
        return self::startsWith($haystack, $needle);
    }

    /**
     * @deprecated
     * @param string $datetime
     * @return bool
     */
    public static function date_past($datetime)
    {
        return self::datePast($datetime);
    }

    /**
     * @deprecated
     * @param string $datetime
     * @return bool
     */
    public static function date_future($datetime)
    {
        return self::dateFuture($datetime);
    }

}
