<?php
namespace Platform;

use Platform\Html;

class Admin {

    /**
     * @return void
     */
    public static function setup()
    {
        add_action('admin_enqueue_scripts', [__CLASS__, 'css']);
        add_action('dashboard_glance_items', [__CLASS__, 'dashboardPostTypes']);
    }

    /**
     * @return void
     */
    public static function css()
    {
        if (file_exists(Setup::getPlatformPath().'/css/admin.css')) {
            wp_register_style('sd_admin_style', Setup::getPlatformUri().'/css/admin.css');
            wp_enqueue_style('sd_admin_style');
        }

        if (file_exists(get_template_directory().'/css/admin.css')) {
            wp_register_style('sd_admin_style2', get_template_directory_uri().'/css/admin.css');
            wp_enqueue_style('sd_admin_style2');
        }
    }

    /**
     * @return void
     */
    public static function dashboardPostTypes()
    {
        $args = ['show_in_menu' => true, '_builtin' => false];
        $post_types = get_post_types($args, 'objects');

        foreach ($post_types as $r) {

            $count = wp_count_posts($r->name);
            $num = number_format($count->publish);
            $text = _n($r->labels->singular_name, $r->labels->menu_name, intval($count->publish));

            if (current_user_can('edit_posts')) {
                $output = '<a href="edit.php?post_type='.$r->name.'">'.$num.' '.$text.'</a>';
            } else {
                $output = '<span>'.$num.' '.$text.'</span>';
            }

            echo '<li class="'.$r->name.'">';
            echo $output;
            echo '</li>';
        }
    }

    /**
     * @return void
     */
    public static function router()
    {
        //holder
    }

}
