<?php
namespace Platform;

// use HTMLPurifier;

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
     * @param mixed $number
     * @param int $decimals
     * @return string
     */
    public static function number($number, $decimals = 0)
    {
        return number_format($number, $decimals);
    }

    /**
     * @param string $str
     * @param int $limit
     * @return string
     */
    public static function abbreviate($str, $limit = 50)
    {
        if (strlen($str) <= $limit) {
            return $str;
        }

        $length = $limit;
        $truncated = substr($str, 0, $length);
        $truncated = trim($truncated);

        $html = '<abbr title="'.self::entities($str).'">';
        $html .= self::entities($truncated);
        $html .= '...';
        $html .= '</abbr>';
        return $html;
    }

    /**
     * @todo
     * @param string $html
     * @return string
     */
    public static function purify($html)
    {
        return $html;
        // $config = HTMLPurifier\Config::createDefault();
        // $config->set('HTML.TidyLevel', 'none'); // Only XSS cleaning now
        // $config->set('Core.Encoding', 'UTF-8');
        // $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        // $config->set('Cache.DefinitionImpl', null);
        // $config->set('Attr.EnableID', true);
        // $config->set('HTML.Trusted', false);
        // $config->set('HTML.SafeObject', true);
        // $config->set('Output.FlashCompat', true);
        // $config->set('Attr.AllowedRel', array('align','lightbox','nofollow','alternate','author','bookmark','external','cannonical','help','license','next','noreferrer','pingback','prefetch','prev','search','sidebar','tag'));
        // $config->set('Attr.AllowedFrameTargets', array('_blank' => true, '_new' => true));
        // $config->set('CSS.AllowTricky', true);
        //
        // $def = $config->getHTMLDefinition(true);
        // $def->addAttribute('img', 'usemap', 'CDATA');
        // $def->addAttribute('iframe','align','Enum#top,left,right,center');
        // $def->addAttribute('iframe', 'allowfullscreen', 'Bool');
        // $def->addAttribute('iframe', 'mozallowfullscreen', 'Bool');
        // $def->addAttribute('iframe', 'webkitallowfullscreen', 'Bool');
        //
        // $purifier = new HTMLPurifier($config);
        // $html = $purifier->purify($html);
        // return html_entity_decode($html);
    }

    /**
     * @param float $numerator
     * @param float $denomonator
     * @return float
     */
    public static function percentage($numerator, $denomonator)
    {
        if ($denomonator == 0) {
            return 0;
        }

        return $numerator / $denomonator * 100;
    }

    /**
     * @param string $str
     * @return string
     */
    public static function nl2br($str)
    {
        $str = self::entities($str);
        $str = nl2br($str);
        return $str;
    }

    /**
     * @param int $count
     * @param string $singular
     * @param string $plural
     * @return string
     */
    public static function pluralise($count, $singular, $plural = null)
    {
        if (!$plural) {
            $plural = $singular.'s';
        }

        if ($count == 1) {
            return sprintf($singular, $count);
        } else {
            return sprintf($plural, $count);
        }
    }

}
