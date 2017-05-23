<?php
namespace Platform;

class Paging {

    public $page;
    public $per_page;
    public $total;
    public $offset;
    public $limit;
    public $anchor;
    public $next_text;
    public $prev_text;
    public $class_pagination = 'pagination';
    public $class_text = 'pagination-text';
    public $class_disabled = 'disabled';
    public $class_active = 'active';
    protected $var_name;

    /**
     * @param int $per_page
     * @param int $page
     * @return void
     */
    public function __construct($per_page = null, $page = null)
    {
        if ($per_page) {
            //do nothing if per_page set
        } elseif (function_exists('get_query_var')) {
            $per_page = get_query_var('posts_per_page');
        }

        if ($per_page) {
            //do nothing if per_page set
        } elseif (function_exists('get_option')) {
            $per_page = get_option('posts_per_page');
        } else {
            $per_page = PER_PAGE;
        }

        if ($page) {
            //do nothing if page set
        } elseif (function_exists('get_query_var')) {
            $page = get_query_var('paged');
        } elseif (isset($_GET['page'])) {
            $page = $_GET['page'];
        } else {
            $page = 0;
        }

        if ($page == 0) {
            $page = 1;
        }

        if (function_exists('get_query_var')) {
            $this->var_name = 'paged';
        } else {
            $this->var_name = 'page';
        }

        $this->page = $page;
        $this->per_page = $per_page;
        $this->offset = ($this->page - 1) * $this->per_page;
        $this->limit = $this->offset.', '.$this->per_page;
    }

    /**
     * @param int $total
     */
    public function setTotal($total)
    {
        $this->total = $total;
    }

    /**
     * @param int $num_show
     * @param string $text_str
     * @return void
     */
    public function display($num_show = 99, $text_str = false)
    {

        $last_page = ceil($this->total / $this->per_page);
        $prev_page = $this->page - 1;
        $next_page = $this->page + 1;

        if ($this->next_text) {
            $next_text = $this->next_text;
        } else {
            $next_text = '<i>&gt;</i>';
        }

        if ($this->prev_text) {
            $prev_text = $this->prev_text;
        } else {
            $prev_text = '<i>&lt;</i>';
        }

        if (!$this->total || $last_page < 2) {
            return false;
        }

        if ($this->anchor) {
            $anchor = '#'.$this->anchor;
        } else {
            $anchor = '';
        }

        $rtn = '';

        if ($text_str) {
            $rtn .= '<span class="'.$this->class_text.'">';
            $rtn .= sprintf($text_str, $this->page, $last_page);
            $rtn .= '</span> ';
        }

        $rtn .= '<ul class="'.$this->class_pagination.'">';

        if ($prev_page > 1) {
            $rtn .= '<li>';
            $rtn .= '<a href="'.Request::addVar($this->var_name, $prev_page).$anchor.'">';
            $rtn .= $prev_text;
            $rtn .= '</a>';
            $rtn .= '</li>';
        } else if ($prev_page == 1) {
            $rtn .= '<li>';
            $rtn .= '<a href="'.Request::removeVar($this->var_name).$anchor.'">';
            $rtn .= $prev_text;
            $rtn .= '</a>';
            $rtn .= '</li>';
        } else {
            $rtn .= '<li class="'.$this->class_disabled.'">';
            $rtn .= '<span>';
            $rtn .= $prev_text;
            $rtn .= '</span>';
            $rtn .= '</li>';
        }

        if ($num_show > 0) {

            if ($last_page > $num_show) {

                $pages_either_side = floor(($num_show - 1) / 2);
                $gap_left_side = $this->page - 1 - 1;
                $gap_right_side = $last_page - $this->page - 1;
                $extra_left_side = $pages_either_side - $gap_right_side;
                $extra_right_side = $pages_either_side - $gap_left_side;
                $pages_left_side = $pages_either_side + ($extra_left_side > 0 ? $extra_left_side : 0);
                $pages_right_side = $pages_either_side + ($extra_right_side > 0 ? $extra_right_side : 0);

                $pages_to_show = array();
                $pages_to_show[1] = 1;

                for ($i=$pages_left_side; $i>0; $i--) {
                    $pg = $this->page - $i;
                    $pages_to_show[$pg] = $pg;
                }

                $pages_to_show[$this->page] = $this->page;

                for ($i=0; $i<=$pages_right_side; $i++) {
                    $pg = $this->page + $i;
                    $pages_to_show[$pg] = $pg;
                }

                $pages_to_show[$last_page] = $last_page;

                foreach ($pages_to_show as $key => $num) { //cleanup
                    if ($num < 1) {
                        unset($pages_to_show[$key]);
                    }
                    if ($num > $last_page) {
                        unset($pages_to_show[$key]);
                    }
                }

            } else {
                $pages_to_show = range(1, $last_page);
            }

            $prev_i = 0;
            foreach ($pages_to_show as $i) {

                if ($prev_i != $i - 1) {
                    $rtn .= '<li class="'.$this->class_disabled.'">';
                    $rtn .= '<span>';
                    $rtn .= '&hellip;';
                    $rtn .= '</span>';
                    $rtn .= '</li>';
                }

                if ($i == $this->page) {
                    $rtn .= '<li class="'.$this->class_active.'">';
                    $rtn .= '<span>';
                    $rtn .= $i;
                    $rtn .= '</span>';
                    $rtn .= '</li>';
                } elseif ($i == 1) {
                    $rtn .= '<li>';
                    $rtn .= '<a href="'.Request::removeVar($this->var_name).$anchor.'">';
                    $rtn .= $i;
                    $rtn .= '</a>';
                    $rtn .= '</li>';
                } else {
                    $rtn .= '<li>';
                    $rtn .= '<a href="'.Request::addVar($this->var_name, $i).$anchor.'">';
                    $rtn .= $i;
                    $rtn .= '</a>';
                    $rtn .= '</li>';
                }

                $prev_i = $i;

            }

        } else {
            $rtn .= '<li class="'.$this->class_disabled.'">';
            $rtn .= '<span>';
            $rtn .= ___('Page %d of %d', $this->page, $last_page);
            $rtn .= '</span>';
            $rtn .= '</li>';
        }

        if ($next_page <= $last_page) {
            $rtn .= '<li>';
            $rtn .= '<a href="'.Request::addVar($this->var_name, $next_page).$anchor.'">';
            $rtn .= $next_text;
            $rtn .= '</a>';
            $rtn .= '</li>';
        } else {
            $rtn .= '<li class="'.$this->class_disabled.'">';
            $rtn .= '<span>';
            $rtn .= $next_text;
            $rtn .= '</span>';
            $rtn .= '</li>';
        }

        $rtn .= '</ul>';

        echo $rtn;

    }

    /**
     * @return void
     */
    public static function setup()
    {
        add_filter('redirect_canonical', array(__CLASS__, 'disableCanonical'), 10, 2);
    }

    /**
     * @param string $redirect_url
     * @param string $requested_url
     * @return string
     */
    public static function disableCanonical($redirect_url, $requested_url)
    {

        if (strpos($redirect_url, '/page/') !== false) {
            return false;
        }

        return $redirect_url;

    }

    /**
     * @return void
     */
    public static function draw()
    {
        $paging = new self();
        $paging->total = $GLOBALS['wp_query']->found_posts;
        $paging->next_text = 'Next<i class="icon-arrow-right"></i>';
        $paging->prev_text = '<i class="icon-arrow-left"></i>Prev';
        $paging->display();
    }

}
