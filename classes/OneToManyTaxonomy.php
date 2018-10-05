<?php
/**
 * Class that creates a one to many style taxonomy within Wordpress. Useful for when you need to force the user to
 * select one term per post
 *
 * Created by PhpStorm.
 * User: dgriffin
 * Date: 05/10/2018
 * Time: 16:15
 */

namespace Platform;


class OneToManyTaxonomy
{
    protected $taxonomy, $post_types, $args, $disable_parents;

    /**
     * OneToManyTaxonomy constructor.
     *
     * Here we change the meta box to single select, register the taxonomy & the save and edit actions
     */
    public function __construct()
    {
        if($this->disable_parents){
            $this->args += ['meta_box_cb' => [$this, 'meta_box_no_parents']];
        } else {
            $this->args += ['meta_box_cb' => [$this, 'meta_box']];
        }
        register_taxonomy($this->taxonomy, $this->post_types, $this->args);
        foreach ($this->post_types as $type){
            add_action('save_post_'.$type, [$this, 'save_taxonomy_meta_box']);
        }
        add_action('edit_form_top', [$this, 'show_required_field_error_msg']);
    }

    /**
     * Replaces the metabox with a single select box
     *
     * @param $post
     */
    public function meta_box($post)
    {
        $terms = get_terms($this->taxonomy, array('hide_empty' => false));
        $post  = get_post();
        $object_terms = wp_get_object_terms($post->ID, $this->taxonomy, array( 'orderby' => 'term_id', 'order' => 'ASC'));
        $name  = '';
        if (!is_wp_error($object_terms)) {
            if (isset($object_terms[0]) && isset($object_terms[0]->name)) {
                $name = $object_terms[0]->name;
            }
        }
        foreach ( $terms as $term ) {
            ?>
            <label title='<?php esc_attr_e($term->name); ?>'>
                <input type="radio" name="taxonomy" value="<?php esc_attr_e( $term->name ); ?>" <?php checked( $term->name, $name ); ?>>
                <span><?php esc_html_e( $term->name ); ?></span>
            </label><br>
            <?php
        }
    }

    /**
     * Handles the no parents option
     */
    public function meta_box_no_parents()
    {
        $parent_terms = get_terms( $this->taxonomy, array( 'hide_empty' => false, 'parent' => 0) );
        foreach ($parent_terms as $key => $term){
            $children = get_term_children($term->term_id, $this->taxonomy);
            if(!empty($children)){
                $term->children = get_terms(['taxonomy' => $this->taxonomy, 'include' => $children, 'hide_empty' => false]);
            } else {
                unset($parent_terms[$key]);
            }
        }
        $post  = get_post();
        $object_terms = wp_get_object_terms( $post->ID, $this->taxonomy, array( 'orderby' => 'term_id', 'order' => 'ASC' ) );
        $name  = '';
        if ( ! is_wp_error( $object_terms ) ) {
            if ( isset( $object_terms[0] ) && isset( $object_terms[0]->name ) ) {
                $name = $object_terms[0]->name;
            }
        }
        $i = 0;
        foreach ($parent_terms as $parent) { ?>
            <div>
                <span><?php esc_attr_e($parent->name); ?></span>
            </div>
            <?php foreach ($parent->children as $child) { ?>
                <label title='<?php esc_attr_e($child->name); ?>'>
                    <input type="radio" name="taxonomy"
                           value="<?php esc_attr_e($child->name); ?>" <?php checked($child->name, $name); ?>>
                    <span><?php esc_html_e($child->name); ?></span>
                </label><br>
                <?php
            }
            if($i < count($parent)){
                echo '<br>';
            }
            $i++;
        }
    }

    /**
     * Handles save
     *
     * @param $post_id
     */
    public function save_taxonomy_meta_box( $post_id )
    {
        if (defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE) {
            return;
        }
        if (!isset( $_POST['taxonomy'])) {
            return;
        }
        $chosen_taxonomy = sanitize_text_field($_POST['taxonomy']);

        // A valid rating is required, so don't let this get published without one
        if (empty($chosen_taxonomy)) {
            $post_type = get_post_type($post_id);
            // unhook this function so it doesn't loop infinitely
            remove_action('save_post_'.$post_type, 'save_taxonomy_meta_box');
            $postdata = array(
                'ID'          => $post_id,
                'post_status' => 'draft',
            );
            wp_update_post($postdata);
        } else {
            $term = get_term_by( 'name', $chosen_taxonomy, $this->taxonomy );
            if (!empty($term) && ! is_wp_error($term) ) {
                wp_set_object_terms($post_id, $term->term_id, $this->taxonomy, false);
            }
        }
    }

    /**
     * Handles the Case of the post type requiring the taxonomy
     *
     * @param $post
     */
    public function show_required_field_error_msg($post)
    {
        if (in_array(get_post_type($post), $this->post_types) && 'auto-draft' !== get_post_status($post) ) {
            $chosen_term = wp_get_object_terms($post->ID, $this->taxonomy, array( 'orderby' => 'term_id', 'order' => 'ASC' ));
            if (is_wp_error($chosen_term) || empty($chosen_term)) {
                printf(
                    '<div class="error below-h2"><p>%s</p></div>',
                    esc_html__( $this->args['label'].' is mandatory for creating a new post')
                );
            }
        }
    }
}