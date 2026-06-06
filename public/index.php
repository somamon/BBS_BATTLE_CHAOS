<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Presentation\Http\Request;
use App\Presentation\Http\Response;
use App\Presentation\Routing\Router;
use App\Presentation\Routing\NotFoundException;
use App\Presentation\Routing\MethodNotAllowedException;

session_start();

$request = Request::fromGlobals();

$router = new Router();
require __DIR__ . '/../src/Presentation/Routing/routes.php';

try {
    $response = $router->dispatch($request);
} catch (NotFoundException) {
    $response = Response::error(404, 'Not Found');
} catch (MethodNotAllowedException $e) {
    $response = Response::error(405, 'Method Not Allowed')
        ->withHeader('Allow', implode(', ', $e->allowed));
} catch (\Throwable $e) {
    error_log((string) $e);

    $env = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production';
    $message = $env === 'development'
        ? (string) $e
        : 'Internal Server Error';

    $response = Response::error(500, $message);
}

$response->send();