<?php

class Controller {
    protected $db;

    public function __construct() {
        $this->db = new Database(); 
    }

    public function model($model) {
        $modelPath = ROOT_PATH . '/app/models/' . $model . '.php';
        if (file_exists($modelPath)) {
            return new $model();
        } else {
            die("Model {$model} not found.");
        }
    }

    public function view($view, $data = []) {
        $viewPath = ROOT_PATH . '/app/views/' . $view . '.php';
        if (file_exists($viewPath)) {
            extract($data);
            require_once $viewPath;
        }
    }
}