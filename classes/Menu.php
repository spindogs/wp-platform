<?php
namespace Platform;

class Menu {

    //protected static $key; //abstract
    //protected static $name; //abstract

    /**
     * @return void
     */
    public static function setup()
    {
        add_theme_support('menus');
        register_nav_menu(static::$key, static::$name);
        add_filter('nav_menu_css_class', [__CLASS__, 'cssClasses'], 10, 2);
    }

    /**
     * @return void
     */
    public static function draw($settings = [])
    {

        $defaults['theme_location'] = static::$key;
        $defaults['container'] = false;
        $defaults['menu_class'] = '';
        $defaults['items_wrap'] = '%3$s';

        $settings = array_merge($defaults, $settings);

        return wp_nav_menu($settings);

    }

    /**
     * @param array $classes
     * @param StdClass $item
     * @return array
     */
    public static function cssClasses($classes, $item)
    {
        $curr_post_type = get_post_type();
        $curr_archive_url = get_post_type_archive_link($curr_post_type);
        $menu_item_url = get_permalink($item->object_id);

        if ($menu_item_url == $curr_archive_url) {
            $classes[] = 'current-menu-parent';
            $classes[] = 'current-'.$curr_post_type.'-parent';
            // print_r($classes);
            // print_r($item);
            // exit;
        }

        return $classes;
    }

}
