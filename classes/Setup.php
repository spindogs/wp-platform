<?php
namespace Platform;

use Platform\Filter;

class Setup {

    protected static $caught_errors = false;
    protected static $platform_path;
    protected static $root_path;
    protected static $app_path;
    protected static $template_path;
    protected static $cache_path;
    protected static $upload_path;
    protected static $salt;
    protected static $lang;
    protected static $default_lang;
    protected static $debug_emails = [];
    protected static $from_email;
    protected static $from_name;

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
        //paths
        self::$root_path = rtrim(ABSPATH, '/');
        self::$platform_path = WP_PLUGIN_DIR.'/wp-platform';
        self::$app_path = get_template_directory().'/app';
        self::$template_path = get_template_directory();
        self::$cache_path = WP_CONTENT_DIR.'/cache';
        self::uploadPath(WP_CONTENT_DIR.'/uploads');

        //vars
        self::$lang = get_current_blog_id();
        self::$salt = NONCE_SALT;

        //setup request
        Request::setup();

        //theme support
        add_theme_support('post-thumbnails');

        //session start
        add_action('init', array(__CLASS__, 'sessionStart'), 1);

        //router
        add_action('send_headers', array(__CLASS__, 'router'), -10);
        add_action('admin_menu', array(__CLASS__, 'router'));

        //error handler
        error_reporting(E_ALL | E_STRICT);
        set_error_handler([__CLASS__, 'errorHandler']);
        set_exception_handler([__CLASS__, 'exceptionHandler']);

        //filters
        add_filter('wp_mail_from', function ($email)
        {
            if (self::$from_email) {
                return self::$from_email;
            } else {
                return $email;
            }
        });

        add_filter('wp_mail_from_name', function ($name)
        {
            if (self::$from_name) {
                return self::$from_name;
            } else {
                return $name;
            }
        });

        //admin setup
        Admin::setup();

        //setup breadcrumb
        Breadcrumb::setup();

        //setup paging
        Paging::setup();
    }

    /**
     * @return void
     */
    public static function router()
    {
        if (class_exists('\\App\\Routes')) {
            \App\Routes::register();
            \App\Routes::dispatch();
        } else {
            return;
        }
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
        $subject = '['.$sitename.' Bug] '.Request::url();

        foreach ($emails as $to) {
            mail($to, $subject, $msg, 'From: bugs@spindogs.com');
        }

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
     * @return stdClass
     */
    public static function root()
    {
        return (object)[
            'path' => self::getPath('root_path', null, null),
            'uri' =>  self::getPath('root_path', null, true),
        ];
    }

    /**
     * @param string $suffix
     * @return string
     */
    public static function rootPath($suffix = null)
    {
        $root_path = self::$root_path;
        $suffix = str_replace($root_path, '', $suffix); //remove duplicate abspath
        return self::getPath('root_path', $suffix);
    }

    /**
     * @return stdClass
     */
    public static function platform()
    {
        return (object)[
            'path' => self::getPath('platform_path', null, null),
            'uri' =>  self::getPath('platform_path', null, true),
        ];
    }

    /**
     * @param string $suffix
     * @return string
     */
    public static function platformPath($suffix = null)
    {
        return self::getPath('platform_path', $suffix);
    }

    /**
     * @param string $suffix
     * @return string
     */
    public static function platformUri($suffix = null)
    {
        return self::getPath('platform_path', $suffix, true);
    }

    /**
     * @return stdClass
     */
    public static function app()
    {
        return (object)[
            'path' => self::getPath('app_path', null, null),
            'uri' =>  self::getPath('app_path', null, true),
            'default_lang' => self::$default_lang,
            'lang' => self::$lang,
        ];
    }

    /**
     * @param string $suffix
     * @return string
     */
    public static function appPath($suffix = null)
    {
        return self::getPath('app_path', $suffix);
    }

    /**
     * @return stdClass
     */
    public static function template()
    {
        return (object)[
            'path' => self::getPath('template_path', null, null),
            'uri' =>  self::getPath('template_path', null, true),
        ];
    }

    /**
     * @param string $suffix
     * @return string
     */
    public static function templatePath($suffix = null)
    {
        return self::getPath('template_path', $suffix);
    }

    /**
     * @return stdClass
     */
    public static function cache()
    {
        return (object)[
            'path' => self::getPath('cache_path', null, null),
            'uri' =>  self::getPath('cache_path', null, true),
        ];
    }

    /**
     * @param string $suffix
     * @return string
     */
    public static function cachePath($suffix = null)
    {
        return self::getPath('cache_path', $suffix);
    }

    /**
     * @param string $suffix
     * @return string
     */
    public static function cacheUri($suffix = null)
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
            $root_path = self::$root_path;
            $rtn = str_replace($root_path, '', $rtn);
        }

        return $rtn;
    }

    /**
     * @deprecated
     * @param string $suffix
     * @return string
     */
    public static function getRootPath($suffix = null)
    {
        return self::rootPath($suffix);
    }

    /**
     * @deprecated
     * @param string $suffix
     * @return string
     */
    public static function getPlatformPath($suffix = null)
    {
        return self::platformPath($suffix);
    }

    /**
     * @deprecated
     * @param string $suffix
     * @return string
     */
    public static function getPlatformUri($suffix = null)
    {
        return self::platformUri($suffix, true);
    }

    /**
     * @deprecated
     * @param string $suffix
     * @return string
     */
    public static function getAppPath($suffix = null)
    {
        return self::appPath($suffix);
    }

    /**
     * @deprecated
     * @param string $suffix
     * @return string
     */
    public static function getTemplatePath($suffix = null)
    {
        return self::templatePath($suffix);
    }

    /**
     * @deprecated
     * @param string $suffix
     * @return string
     */
    public static function getCachePath($suffix = null)
    {
        return self::cachePath($suffix);
    }

    /**
     * @deprecated
     * @param string $suffix
     * @return string
     */
    public static function getCacheUri($suffix = null)
    {
        return self::cacheUri($suffix, true);
    }

}
