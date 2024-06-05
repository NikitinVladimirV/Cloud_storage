<?php

namespace app\core;

use Exception;

class Router
{
    private array $routes;

    public function __construct()
    {
        $this->routes = (include dirname(__FILE__, 2) . '/config/routes.php');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function run(): void
    {
        preg_match('#^[a-z/?]+(\d+)?/?(\d+)?#', $url = $_SERVER['REQUEST_URI'], $params);
        $queryParams = $_SERVER['QUERY_STRING'];
        $method = $_SERVER['REQUEST_METHOD'];

        if ($queryParams) {
            $url = strstr($url, '?', true);
        }

        if (count($params) > 1) {
            $url = preg_split('/\d+/', $url)[0];
        }

        $url = trim($url, '/');
        $nameController = explode('/', $url)[0];

        if (!array_key_exists($url, $this->routes)) {
            throw new Exception('Роут "' . strtoupper($nameController) . '" не найден!');
        }

        $path = 'app\controllers\\' . ucfirst($nameController) . 'Controller';
        if (!class_exists($path)) {
            throw new Exception('Контроллер "' . strtoupper($path) . '" не найден!');
        }

        $action = $this->routes[$url][$method];
        if (!isset($this->routes[$url][$method]) && method_exists($path, $action)) {
            throw new Exception('Не существует экшена "' . strtoupper($method) . '" по адресу ' . strtoupper($url) . '!');
        }

        $controller = new $path;
        echo $controller->$action(count($params) > 1 ? $params[1] : null);
    }
}
