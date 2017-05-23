<?php
namespace Platform;

class Html {

    /**
     * @param string $html
     * @return string
     */
    public static function entities($html)
    {
        return htmlspecialchars($html, ENT_QUOTES, 'UTF-8', true);
    }

    /**
     * @todo
     * @param string $html
     * @return string
     */
    public static function purify($html)
    {
        //TODO purify this
        return $html;
    }

}
