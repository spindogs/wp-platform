<?php
namespace Platform;

class Widget {

    //protected static $shortcode; //abstract
    protected static $widgets = array();

    /**
    * @return void
    */
    public function prepare()
    {
        //this is a placeholder
    }

    /**
    * @return void
    */
    public function display()
    {
        //this is a placeholder
    }

    /**
    * @param array $attrs
    * @return void
    */
    public function generate($attrs=array())
    {
        $attrs = (array)$attrs;

        foreach ($attrs as $key => $val) {

            if (property_exists($this, $key)) {
                $this->{$key} = $val;
            }

        }

        $this->prepare();

        ob_start();
        $this->display();
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    /**
     * @return void
     */
    public static function setup()
    {
        $shortcode = static::$shortcode;
        $widget = new static();
        add_shortcode($shortcode, array($widget, 'generate'));
        self::$widgets[$shortcode] = $widget;
    }

}
