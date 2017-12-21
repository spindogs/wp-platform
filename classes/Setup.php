<?php
namespace Platform;

use Platform\Filter;

class Setup {

    protected static $caught_errors = false;
    public static $platform_path;
    public static $root_path;
    public static $app_path;
    public static $template_path;
    public static $cache_path;
    protected static $upload_path;
    public static $salt;
    protected static $lang;
    protected static $default_lang;
    protected static $debug_emails = [];
    protected static $from_email;
    protected static $from_name;
    protected static $use_deprecated = true;

    public static function __callStatic($name, $args)
    {
        if ($args) {
            $value = reset($args);
        } else {
            $value = null;
        }

        $property = Filter::snakeCase($name);

        if (!property_exists(__CLASS__, $property)) {
            return;
        }

        if ($value !== null) {
            self::${$property} = $value;
        } else {
            return self::${$property};
        }
    }

    /**
     * @return void
     */
    public static function setup()
    {
        //default paths
        if (!self::$root_path) {
            self::$root_path = rtrim(ABSPATH, '/');
        }
        if (!self::$platform_path) {
            self::$platform_path = dirname(dirname(__FILE__));
        }
        if (!self::$app_path) {
            self::$app_path = self::$root_path.'/app';
        }
        if (!self::$template_path) {
            self::$template_path = self::$app_path.'/views';
        }
        if (!self::$cache_path) {
            self::$cache_path = self::$root_path.'/cache';
        }

        //salt
        if (defined('SALT')) {
            self::$salt = SALT;
        }

        //setup request
        Request::setup();
    }

    /**
     * @return void
     */
    public static function setupWordpress()
    {
        //autoloader
        spl_autoload_register(['Platform\Setup', 'autoload']);

        //paths
        self::$root_path = rtrim(ABSPATH, '/');
        self::$platform_path = WP_PLUGIN_DIR.'/wp-platform';
        self::$app_path = get_template_directory().'/app';
        self::$template_path = get_template_directory();
        self::$cache_path = WP_CONTENT_DIR.'/cache';
        self::uploadPath(WP_CONTENT_DIR.'/uploads');

        //vars
        self::$lang = get_current_blog_id();

        //setup request
        Request::setup();

        //deprecated
        add_action('after_setup_theme', [__CLASS__, 'deprecated']);

        //debug_emails
        if (defined('DEBUG_EMAIL')) {
            $emails = explode(',', DEBUG_EMAIL);
            $emails = (array)$emails;
            foreach ($emails as $to) {
                self::debug($to);
            }
        }

        //theme support
        add_theme_support('post-thumbnails');

        //session start
        add_action('after_setup_theme', array(__CLASS__, 'sessionStart'));

        //router
        add_action('init', array(__CLASS__, 'router'), 9999);
        add_action('admin_menu', array(__CLASS__, 'router'));

        //error handler
        error_reporting(E_ALL | E_STRICT);
        set_error_handler([__CLASS__, 'errorHandler']);
        set_exception_handler([__CLASS__, 'exceptionHandler']);

        //filters
        add_filter('wp_mail_from', [__CLASS__, 'filterMailFrom']);
        add_filter('wp_mail_from_name', [__CLASS__, 'filterMailName']);

        //admin setup
        Admin::setup();

        //setup breadcrumb
        Breadcrumb::setup();

        //setup paging
        Paging::setup();
    }

    /**
     * @param string $qualified
     * @return void
     */
    public static function autoload($qualified)
    {
        $namespaces = array(
            'Platform\\Beta' => self::$platform_path.'/beta/',
            'Platform' => self::$platform_path.'/classes/',
            'App\\Controller' => self::$app_path.'/controllers/',
            'App\\Model' => self::$app_path.'/models/',
            'App\\Service' => self::$app_path.'/services/',
            'App\\Widget' => self::$app_path.'/widgets/'
        );

        foreach ($namespaces as $prefix => $base_dir) {

            $prefix_length = strlen($prefix);
            $haystack_to_match = substr($qualified, 0, $prefix_length);

            if ($haystack_to_match != $prefix) {
                continue; //skip if not using this namespace prefix
            }

            $sub_namespace = substr($qualified, $prefix_length);
            $sub_namespace = ltrim($sub_namespace, '\\');
            $parts = explode('\\', $sub_namespace);
            $class = array_pop($parts);

            if ($parts) {
                $sub_path = implode('/', $parts);
                $sub_path .= '/';
            } else {
                $sub_path = '';
            }

            $file = $base_dir.$sub_path.$class.'.php';

            if (file_exists($file)) {
                require_once($file);
            }

            return;

        }

    }

    /**
     * @return void
     */
    public static function router()
    {
        $filepath = Setup::$app_path.'/routes.php';

        if (file_exists($filepath)) {
            require($filepath);
        } else {
            return;
        }

        $view = Route::dispatch(); //deprecated route mechanism
    }

    /**
     * @return void
     */
    public static function sessionStart()
    {
        if (!session_id()) {
            session_start();
        }
    }

    /**
     * @return void
     */
    public static function deprecated()
    {
        if (!self::$use_deprecated) {
            return;
        }

        define('TEMPLATE_URI', get_template_directory_uri());
        define('TEMPLATE_PATH', get_template_directory());
        define('HTTP', Request::getScheme());
        define('HOST', Request::getHost());
        define('URI', Request::getPath());
        define('QSTRING', Request::getQuery());
        define('HTTP_URL', Request::getHttpUrl());
    }

    /**
     * @param string $errno
     * @param string $errmsg
     * @param string $filename
     * @param string $linenum
     * @return bool
     */
    public static function errorHandler($errno, $errmsg, $filename, $linenum)
    {
        if (intval(ini_get('error_reporting')) < 1) { //this error has been @ suppressed
            return true;
        }

        if (!(error_reporting() & $errno)) {
            return true; //this error code is not included in error_reporting
        }

        if (strpos($filename, 'wp-content/themes') !== false) {
            //report bug notices from theme files
        } elseif (strpos($filename, 'wp-content/plugins/wp-platform') !== false) {
            //report bug notices from sd-platform
        } else {
            //we're not interested in any other bug notices
            return false;
        }

        if (!self::$caught_errors) {
            self::emailError($errno, $errmsg, $filename, $linenum);
            self::$caught_errors = true;
        }

        return false; //let the defaults kick in
    }

    /**
     * @param Exception $exception
     * @return void
     */
    public static function exceptionHandler($exception)
    {
        if (!self::$caught_errors) {

            $type = get_class($exception);
            $message = $exception->getMessage();
            $filename = $exception->getFile();
            $line = $exception->getLine();
            $trace = $exception->getTraceAsString();

            if ($type == 'ErrorException') {
                $code = $exception->getSeverity();
            } else {
                $code = $exception->getCode();
            }

            self::emailError($code, $message, $filename, $line, $trace);
            self::$caught_errors = true;

        }

    }

    /**
     * @param string $code
     * @param string $message
     * @param string $filename
     * @param string $line
     * @param string $trace
     * @return void
     */
    public static function emailError($code, $message, $filename, $line, $trace='')
    {
        if (!self::$debug_emails) {
            return;
        }

        $msg = '';
        $msg .= '-- IP: '.(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '')."\n";
        $msg .= '-- UA: '.(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '')."\n";
        $msg .= '-- Code: '.$code."\n";
        $msg .= '-- File: '.$filename."\n";
        $msg .= '-- Line: '.$line."\n";
        $msg .= '-- Message: '.$message."\n";
        $msg .= "\n";
        $msg .= $trace."\n";

        $emails = self::$debug_emails;
        $sitename = get_bloginfo('name');
        $subject = '['.$sitename.' Bug] '.Request::getUrl();

        foreach ($emails as $to) {
            mail($to, $subject, $msg, 'From: bugs@spindogs.com');
        }

    }

    /**
     * @param mixed $lang
     * @return void
     */
    public static function defaultLang($lang)
    {
        self::$default_lang = $lang;
    }

    /**
     * @param mixed $salt
     * @return void
     */
    public static function salt($salt)
    {
        self::$salt = $salt;
    }

    /**
     * @param mixed $email
     * @return void
     */
    public static function debug($email)
    {
        self::$debug_emails[] = $email;
    }

    /**
     * @param mixed $email
     * @return void
     */
    public static function emailFrom($email, $name = null)
    {
        self::$from_email = $email;
        self::$from_name = $name;
    }

    /**
     * @param bool $use_deprecated
     * @return void
     */
    public static function useDeprecated($use_deprecated)
    {
        self::$use_deprecated = $use_deprecated;
    }

    /**
     * @param string $suffix
     * @return string
     */
    public static function getRootPath($suffix = null)
    {
        $root_path = self::$root_path;
        $suffix = str_replace($root_path, '', $suffix); //remove duplicate abspath
        return self::getPath('root_path', $suffix);
    }

    /**
     * @param string $suffix
     * @return string
     */
    public static function getPlatformPath($suffix = null)
    {
        return self::getPath('platform_path', $suffix);
    }

    /**
     * @param string $suffix
     * @return string
     */
    public static function getPlatformUri($suffix = null)
    {
        return self::getPath('platform_path', $suffix, true);
    }

    /**
     * @param string $suffix
     * @return string
     */
    public static function getAppPath($suffix = null)
    {
        return self::getPath('app_path', $suffix);
    }

    /**
     * @param string $suffix
     * @return string
     */
    public static function getTemplatePath($suffix = null)
    {
        return self::getPath('template_path', $suffix);
    }

    /**
     * @param string $suffix
     * @return string
     */
    public static function getCachePath($suffix = null)
    {
        return self::getPath('cache_path', $suffix);
    }

    /**
     * @param string $suffix
     * @return string
     */
    public static function getCacheUri($suffix = null)
    {
        return self::getPath('cache_path', $suffix, true);
    }

    /**
     * @param string $name
     * @param string $suffix
     * @param bool $is_uri
     * @return string
     */
    protected static function getPath($name, $suffix = null, $is_uri = false)
    {
        $suffix = trim($suffix, '/');

        if ($suffix) {
            $suffix = '/'.$suffix;
        } else {
            $suffix = '';
        }

        $rtn = self::${$name};
        $rtn .= $suffix;

        if ($is_uri) {
            $root_path = self::getRootPath();
            $rtn = str_replace($root_path, '', $rtn);
        }

        return $rtn;
    }

    /**
     * @return mixed
     */
    public static function getLang()
    {
        return self::$lang;
    }

    /**
     * @return mixed
     */
    public static function getDefaultLang()
    {
        return self::$default_lang;
    }

    /**
     * @param string $email
     * @return string
     */
    public static function filterMailFrom($email)
    {
        if (self::$from_email) {
            return self::$from_email;
        } else {
            return $email;
        }
    }

    /**
     * @param string $name
     * @return string
     */
    public static function filterMailName($name)
    {
        if (self::$from_name) {
            return self::$from_name;
        } else {
            return $name;
        }
    }

}
