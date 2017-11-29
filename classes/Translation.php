<?php
namespace Platform;

use DateTime;
use Platform\Setup;
use Platform\Sql;
use Platform\Model;
use Platform\Collection;

class Translation extends Model {

    protected static $table = '_Translations';
    protected static $cache = [];
    protected static $debug;

    public $id;
    public $uid;
    public $date_accessed;
    public $lang;
    public $value;
    public $langs = [];

    /**
     * @return string
     */
    public function query()
    {
        $q = 'SELECT SQL_CALC_FOUND_ROWS
                    t.*,
                    tl.lang AS lang,
                    tl.value AS `value`
                FROM _Translations AS t
                LEFT JOIN _TranslationLang AS tl
                    ON tl.translation_id = t.id
                    {where_lang}
                WHERE 1=1
                    {where_id}
                ORDER BY
                    t.uid ASC
                {limit}';

        $this->fields([
            'id' => [
                'column' => 't.id',
                'type' => self::INTEGER
            ],
            'lang' => [
                'column' => 'tl.lang',
                'type' => self::STRING
            ],
            'date_accessed' => [
                'type' => self::DATETIME
            ]
        ]);

        return $q;
    }

    /**
     * @return void
     */
    protected function save()
    {
        //_Translations
        parent::save();

        //_TranslationLang
        foreach ($this->langs as $lang => $val) {
            $values = [];
            $values['translation_id'] = $this->id;
            $values['lang'] = $lang;
            $values['value'] = $val;
            Sql::autoCreate($values, '_TranslationLang', 'UPDATE', false);
        }

    }

    /**
     * @param string $lang The language that we want to load all translation for.
     * @return void
     */
    public static function build($lang = null)
    {
        if (!Setup::app()->default_lang) {
            return; //need to set default lang to active translations
        }
        if (!$lang) {
            $lang = Setup::app()->lang;
        }
        if (isset(self::$cache[$lang])) {
            return;
        }

        $collection = new Collection(__CLASS__);
        $collection->indexBy('uid');
        $collection->where('lang', $lang);
        $translations = $collection->getAll();

        self::$cache[$lang] = $translations;
    }

    /**
     * @param string $uid
     * @param mixed[] $params
     * @return string The translated string with placeholders replaced.
     */
    public static function _($uid, ...$params)
    {
        return self::get($uid, ...$params);
    }

    /**
     * @param string $uid
     * @param mixed[] $params
     * @return string The translated string with placeholders replaced.
     */
    public static function get($uid, ...$params)
    {
        $lang = Setup::app()->lang;
        $translation = self::getByLang($uid, $lang);

        $rtn = sprintf($translation, ...$params);
        $rtn = str_replace('&amp;', '&', $rtn);
        $rtn = str_replace('&', '&amp;', $rtn);

        return $rtn;
    }

    /**
     * @param string $uid The translation we are trying to get.
     * @param string $lang The language that we want to get the translation in.
     * @return string The translated string.
     */
    public static function getByLang($uid, $lang)
    {
        self::build($lang);

        $default_lang = Setup::app()->default_lang;

        if (isset(self::$cache[$lang][$uid])) {
            $translation = self::$cache[$lang][$uid];
        } elseif (self::$debug) {
            $translation = new self();
            $translation->uid = $uid;
            $translation->date_accessed = new DateTime();
            $translation->langs = [$default_lang => $uid];
            $translation->create(); //auto create the english translation in debug mode
            $translation->value = $uid; //this is used below
            self::$cache[$lang][$uid] = $translation; //add to cache
            return $uid;
        } else {
            return $uid;
        }

        if (self::$debug) {
            $modify = new self($translation->id);
            $modify->date_accessed = new DateTime();
            $modify->update();
        }

        return $translation->value;
    }

    /**
     * @return void
     */
    public static function debug()
    {
        self::$debug = true;
    }

    /**
     * @return void
     */
    public static function setup()
    {
        add_action('admin_menu', [__CLASS__, 'adminMenu']);
    }

    /**
     * @return void
     */
    public static function adminMenu()
    {
        add_menu_page(
            'Translations',
            'Translations',
            'publish_pages',
            Setup::platform()->uri.'/admin/translation-list',
            function(){},
            'dashicons-editor-alignleft',
            81
        );
    }

    /**
     * @return void
     */
    public static function install()
    {
        global $Database;
        global $wpdb;

        $q = [];

        $q[] = 'CREATE TABLE `_Translations` (
                    `id` int(11) NOT NULL,
                    `uid` varchar(255) NOT NULL,
                    `date_accessed` datetime NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8';

        $q[] = 'ALTER TABLE `_Translations`
                ADD PRIMARY KEY (`id`)';

        $q[] = 'ALTER TABLE `_Translations`
                MODIFY `id` int(11) NOT NULL AUTO_INCREMENT';

        $q[] = 'CREATE TABLE `_TranslationLang` (
                    `translation_id` int(11) NOT NULL,
                    `lang` varchar(255) NOT NULL,
                    `value` text NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8';

        $q[] = 'ALTER TABLE `_TranslationLang`
                ADD PRIMARY KEY (`translation_id`,`lang`) USING BTREE';

        foreach ($q as $sql) {
            if (isset($Database)) {
                $raw = $Database->query($sql);
            } else {
                $raw = $wpdb->query($sql);
            }
        }

    }

}
