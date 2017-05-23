<?php
namespace Platform;

class Controller {

    protected $template_path;
    protected $view;
    protected $data = array();

    /**
     * @param string $action
     * @param array $params
     * @return void
     */
    public function call($action, $params=array())
    {
        if (!$action) {
            $this->http404();
        }

        if (!method_exists($this, $action)) {
            $this->http404();
        }

        $this->middleware($action);

        $callback = array($this, $action);

        if (is_callable($callback)) {
            call_user_func_array($callback, $params);
        } else {
            $this->http404();
        }

    }

    /**
     * @param string $name
     * @return void
     */
    public function render($name = null)
    {
        $view = $this->getView($name);

        if (!headers_sent()) {
            header('HTTP/1.1 200 OK');
        }

        $view->render();
    }

    /**
    * @param mixed $data
    * @return void
    */
    public function json($data)
    {
        header('HTTP/1.1 200 OK');
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * @param string $controller_name
     * @param string $action
     * @param array $params
     * @return void
     */
    public function uses($controller_name, $action, $params=array())
    {
        if (!class_exists($controller_name)) {
            $controller_name = 'App\\Controller\\'.$controller_name;
        }

        $controller = new $controller_name();
        $controller->call($action, $params);

        $this->data = array_merge($this->data, $controller->data);

    }

    /**
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function data($key, $value=NULL)
    {
        if (isset($value)) {
            $this->setData($key, $value);
            return true;
        } else {
            return $this->getData($key);
        }

    }

    /**
     * @param string $name
     * @return View
     */
    public function getView($name = null)
    {
        if ($name) {
            $this->setView($name); //set default view
        }

        if ($this->template_path) {
            $template_path = $this->template_path;
        } else {
            $template_path = Setup::getTemplatePath();
        }

        $view = new View();
        $view->name = $name;
        $view->data = $this->data;
        $view->filepath = $template_path.'/'.$this->view.'.php';

        if (!file_exists($view->filepath)) {
            throw new Exception($view->filepath.' not found');
            return;
        }

        return $view;
    }

    /**
    * @param string $view
    * @return void
    */
    public function setView($view)
    {
        $this->view = $view;
    }

    /**
     * @param string $action
     * @return void
     */
    protected function middleware($action)
    {
        //this is a hook placeholder
    }

    /**
     * @return void
     */
    public function http404()
    {
        header('HTTP/1.0 404 Not Found');
        echo '404';
        exit;
    }

    /**
     * @return void
     */
    public function http500()
    {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'A bug notice has been delivered to developers';
        exit;
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function getData($key)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        } else {
            return null;
        }
    }

    /**
     * @param string $key
     * @param string $value
     * @return void
     */
    protected function setData($key, $value)
    {
        $this->data[$key] = $value;
    }

}
