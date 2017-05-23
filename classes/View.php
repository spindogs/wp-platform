<?php
namespace Platform;

class View {

    public $name;
    public $filepath;
    public $data;

    /**
     * @return void
     */
    public function render()
    {
        extract($this->data);
        require($this->filepath);
    }

    /**
     * @return string
     */
    public function html()
    {
        ob_start();
        $this->render();
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

}
