<?php
namespace Platform;

class Collection {

    /**
     * @deprecated
     * @param array $posts
     * @return array
     */
    public static function convert($posts=null)
    {
        $rtn = array();

        if ($posts !== null) {
            //do nothing if array passed
        } elseif (isset($GLOBALS['posts'])) {
            $posts = $GLOBALS['posts'];
        } else {
            return array();
        }

        foreach ($posts as $wp_post) {
            $id = $wp_post->ID;
            $rtn[] = PostType::getPost($id);
        }

        return $rtn;
    }

}
