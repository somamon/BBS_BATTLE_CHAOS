<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Presentation\Routing\Router;

$router = new Router();

require_once __DIR__ . '/../src/Presentation/Routing/routes.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
var_dump($_SERVER['REQUEST_METHOD']);

$router->dispatch($method, $path);
