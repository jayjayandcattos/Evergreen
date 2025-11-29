<?php

class App {
    protected $controller = 'CustomerController'; // Default controller
    protected $method = 'account'; // Default method
    protected $params = []; // Parameters for the method

    public function __construct() {
        $url = $this->parseUrl();

        if (!empty($url) && file_exists(ROOT_PATH . '/app/controllers/' . ucfirst($url[0]) . 'Controller.php')) {
            $this->controller = ucfirst($url[0]) . 'Controller';
            unset($url[0]);
        }
        $this->controller = new $this->controller;

        if (isset($url[1])) {
            if (method_exists($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            } else {
                if (method_exists($this->controller, 'index')) {
                    array_unshift($url, $this->method);
                    $this->method = 'index';
                }
            }
        }

        $this->params = $url ? array_values($url) : [];

        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    public function parseUrl() {
        if (isset($_GET['url'])) {
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
        return [];
    }
}