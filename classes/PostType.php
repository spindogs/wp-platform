<?php
namespace Platform;

class PostType {

    //protected static $custom_type; //abstract
    protected static $models = array();

    /**
     * @param WP_Post $post
     */
    public function __construct($post)
    {
        foreach (get_object_vars($post) as $key => $value) {
            $this->{$key} = $value;
        }

        $custom_fields = get_fields($this->ID);

        foreach ($custom_fields as $key => $value) {
            $this->{$key} = $value;
        }

        $this->url = get_permalink($this->ID);
    }

    /**
     * @return void
     */
    public static function setup()
    {
        $static = get_called_class();
        $custom_type = $static::$custom_type;

        if (!$custom_type) {
            throw new Exception('You must define $custom_type in your PostType model');
        }

        self::$models[$custom_type]['class'] = $static;

        add_action('wp', array($static, 'listPage'));
    }

    /**
     * @return void
     */
    public static function listPage()
    {
        global $wp_query;

        if (is_admin()) {
            return;
        }

        $custom_type = static::$custom_type;
        $query_type = $wp_query->get('post_type');

        if (
            !$wp_query->is_posts_page &&
            !$wp_query->is_date &&
            !$wp_query->is_year &&
            !$wp_query->is_month &&
            !$wp_query->is_time &&
            !$wp_query->is_author &&
            !$wp_query->is_category &&
            !$wp_query->is_tag &&
            !$wp_query->is_home
        ) {

            if ($query_type != $custom_type) {
                return;
            }

            if (!$wp_query->is_archive()) {
                return;
            }

            $base_blog = get_site_url(1);
            $curr_blog = get_bloginfo('url');
            $curr_prefix = str_replace($base_blog, '', $curr_blog);

            $uri = $_SERVER['REQUEST_URI'];
            $path = parse_url($uri, PHP_URL_PATH);
            $path = str_replace($curr_prefix, '', $path); //remove multisite blog prefix
            $path = trim($path, '/');
            $page = get_page_by_path($path);

        } else {
            $post_id = get_option('page_for_posts');
            $page = get_post($post_id);
        }

        if (!$page) {
            return;
        }

        if (
            !$wp_query->is_author &&
            !$wp_query->is_category &&
            !$wp_query->is_tag &&
            !$wp_query->is_tax
        ) {
            $wp_query->queried_object_id = $page->ID;
            $wp_query->queried_object = $page;
            $wp_query->queried_object->ancestors = array();
            $wp_query->is_singular = 1;
        }

        $wp_query->post = $page;
        $wp_query->is_page = 1;
        $wp_query->is_404 = 0;
        $wp_query->reset_postdata();

        $GLOBALS['wp_the_query'] = $wp_query;
    }

    /**
     * @param int $id
     * @return self
     */
    public static function getById($id)
    {
        $post = get_post($id);
        $model = new static($post);
        return $model;
    }

}
