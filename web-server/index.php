<?php
error_reporting(E_ALL);
ini_set('display_errors', true);
header('Content-Type: Application/json');

session_start();

require_once 'app/core/autoload.php';

use app\core\Router;

$router = new Router;

try {
    $router->run();
} catch (Exception $e) {
    echo $e->getMessage();
}
