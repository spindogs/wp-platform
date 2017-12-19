<?php
namespace Platform;

class Breadcrumb {

    public $sep;
    protected $nodes;

    /**
     * @return void
     */
    public function generate()
    {
        global $wp_query;
        global $post;
        //print_r($wp_query);exit;

        if (is_feed()) {
            return;
        }

        //reset
        $this->nodes = array();

        //single
        if (empty($post)) {
            //do nothing
        } elseif (is_single() || is_page()) {
            $this->nodes[] = array('name' => $post->post_title, 'url' => get_permalink($post->ID));
        }

        //ancestors
        if (empty($post)) {
            //do nothing
        } elseif (!is_single() && !is_page()) {
            //do nothing
        } else if ($ancestors = get_post_ancestors($post->ID)) {
            foreach ($ancestors as $post_id) {
                $this->nodes[] = array(
                    'name' => get_the_title($post_id),
                    'url' => get_permalink($post_id)
                );
            }
        }

        //tag
        if (is_tag()) {
            $this->nodes[] = array(
                'name' => single_tag_title('', false),
                'url' => get_tag_link($wp_query->queried_object->term_id)
            );
        }

        //taxonomy
        /*if (is_tax()) {

            foreach ($wp_query->query as $field => $value) {

                if (!taxonomy_exists($field)) {
                    continue;
                }

                $term = get_term_by('slug', $value, $field);

                $this->nodes[] = array(
                    'name' => $term->name,
                    'url' => get_category_link($term->term_id)
                );

            }

        } else*/
        if (is_category() || is_tax()) {
            if (isset($wp_query->queried_object)) {
                $this->nodes[] = array(
                    'name' => $wp_query->queried_object->name,
                    'url' => get_category_link($wp_query->queried_object->term_id)
                );
            }
        }

        //archive
        if (is_page()) {
            //do nothing
        } elseif (is_single() || is_category() || is_archive() || is_home()) {

            $post_type = get_post_type();

            if (!$post_type) {
                //do nothing
            } elseif ($post_type == 'post' && $posts_page_id = get_option('page_for_posts')) {
                $this->nodes[] = array('name' => get_the_title($posts_page_id), 'url' => get_permalink($posts_page_id));
            } else {
                $post_type_obj = get_post_type_object($post_type);
                $this->nodes[] = array('name' => $post_type_obj->labels->name, 'url' => get_post_type_archive_link($post_type));
            }

        }

        //home
        $this->nodes[] = array('name' => 'Home', 'url' => '/');

        $this->nodes = array_reverse($this->nodes);

    }

    /**
     * @param string $name
     * @param string $url
     * @param int $offset
     * @return void
     */
    public function addNode($name, $url, $offset=0)
    {

        $first_slice = $this->nodes;

        if ($offset) {
            $last_slice = array_splice($first_slice, $offset);
        } else {
            $last_slice = array();
        }

        $node = array(
            'name' => $name,
            'url' => $url);

        $this->nodes = array_merge($first_slice, array($node), $last_slice);

    }

    /**
     * @param string $url
     * @return void
     */
    public function removeNode($url)
    {

        $url = trim($url, '/');

        foreach ($this->nodes as $i => $r) {
            $r['url'] = relative_url($r['url']);
            $r['url'] = trim($r['url'], '/');
            if ($r['url'] != $url) {
                continue;
            }
            unset($this->nodes[$i]);
            break;
        }

    }

    /**
     * @param string $sep
     * @return void
     */
    public static function setup($sep = '&raquo;')
    {
        $GLOBALS['breadcrumb'] = new self();
        $GLOBALS['breadcrumb']->sep = $sep;
        add_action('template_redirect', array($GLOBALS['breadcrumb'], 'generate'));
    }

    /**
     * @return void
     */
    public static function display()
    {

        $i = 1;
        $num_nodes = count($GLOBALS['breadcrumb']->nodes);

        foreach ($GLOBALS['breadcrumb']->nodes as $r) {

            $r = (object)$r;
            echo $i < $num_nodes ? '<a href="'.$r->url.'">'.$r->name.'</a> '.$GLOBALS['breadcrumb']->sep.' ' : '<span>'.$r->name.'</span>';
            $i++;
        }

    }

}
