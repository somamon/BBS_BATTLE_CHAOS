<?php

namespace App\Presentation\Routing;

class Router
{
    private array $routes = [];
    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }
    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $handler = $this->routes[$method][$path] ?? null;

        if (!$handler) {
            http_response_code(404);
            echo 'Not Found';
            return;
        }

        [$controller, $action] = $handler;

        (new $controller())->$action();
    }
}
