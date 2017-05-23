<?php
namespace Platform;

class PostType {

    //protected static $custom_type; //abstract
    protected static $models = array();

    public $id;
    public $name;
    public $url;
    public $post_type;
    public $parent_id;
    public $date_published;
    public $image;
    public $excerpt;
    public $content;

    /**
     * @param int $id
     * @return void
     */
    public function __construct($id=false)
    {
        $this->id = $id;
    }

    /**
     * @return void
     */
    public function load()
    {
        $this->name = get_the_title($this->id);
        $this->url = get_permalink($this->id);
        $this->post_type = get_post_type($this->id);
        $this->parent_id = get_post_field('post_parent', $this->id);
        $this->image = array();
        $this->content = get_post_field('post_content', $this->id);
        $this->content = apply_filters('the_content', $this->content);
        $this->date_published = get_the_date('Y-m-d H:i:s', $this->id);
        $this->date_published = Filter::from_mysqltime($this->date_published);

        if (has_post_thumbnail($this->id)) {
            $attachment_id = get_post_thumbnail_id($this->id);
        } else {
            $attachment_id = false;
        }

        if ($attachment_id) {

            $sizes = get_intermediate_image_sizes();
            $this->image['url'] = wp_get_attachment_url($attachment_id);

            foreach ($sizes as $size) {
                $this->image['sizes'][$size] = wp_get_attachment_image_src($attachment_id, $size);
                $this->image['sizes'][$size] = reset($this->image['sizes'][$size]);
            }

        }

        if (function_exists('get_fields')) {
            $custom_fields = get_fields($this->id);
            $this->map($custom_fields);
        }

        $this->loader();

    }

    /**
     * @return void
     */
    public function loader()
    {
        //this is a placeholder
    }

    /**
     * @param array $row
     * @return void
     */
    public function map($row)
    {
        if (!$row) {
            return;
        }

        $row = (array)$row;
        $fields = get_object_vars($this);

        foreach ($row as $key => $val) {

            if (!array_key_exists($key, $fields)) {
                continue;
            }

            $this->{$key} = $val;

        }

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
        $post_type = $wp_query->get('post_type');

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

            if ($post_type != $custom_type) {
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
     * @return Platform\PostType
     */
    public static function getPost($id=false)
    {
        if (!$id) {
            $id = get_the_ID();
        }

        if (!$id) {
            return false;
        }

        $models = self::$models;
        $post_type = get_post_type($id);

        if (!$post_type) {
            return false;
        }

        if (isset($models[$post_type]['class'])) {
            $class = $models[$post_type]['class'];
        } else {
            $class = get_called_class();
        }

        $post = new $class($id);
        $post->load();

        return $post;

    }

    /**
     * @return array
     */
    public static function getAll()
    {
        $args = array(
            'post_type' => static::$custom_type,
            'posts_per_page' => -1
        );

        $rtn = get_posts($args);
        $rtn = Collection::convert($rtn);
        return $rtn;
    }

    /**
     * @param int $limit
     * @return array
     */
    public static function getLatest($limit)
    {
        $args = array(
            'post_type' => static::$custom_type,
            'orderby' => 'date',
            'order' => 'DESC',
            'posts_per_page' => $limit
        );

        $rtn = get_posts($args);
        $rtn = Collection::convert($rtn);
        return $rtn;
    }

    /**
     * @return array
     */
    public static function getFeatured()
    {
        $meta_query = array();
        $meta_query[] = array(
            'key' => 'is_featured',
            'compare' => '=',
            'value' => true
        );

        $args = array(
            'post_type' => static::$custom_type,
            'posts_per_page' => -1,
            'meta_query' => $meta_query
        );

        $rtn = get_posts($args);
        $rtn = Collection::convert($rtn);
        return $rtn;
    }

}
