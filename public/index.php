<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Container;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;
use App\Presentation\Routing\Router;
use App\Presentation\Routing\NotFoundException;
use App\Presentation\Routing\MethodNotAllowedException;

// セッションのセキュリティ強化：JS から触れない / クロスサイト送信を抑止 /
// 未初期化IDを採用しない（セッション固定対策の土台）。HTTPS では Secure を付与。
$https = ($_SERVER['HTTPS'] ?? '') !== '' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'secure'   => $https,
    'samesite' => 'Lax',
]);
session_start();

$request = Request::fromGlobals();

$container = Container::build();
$router    = new Router(fn(string $class): object => $container->get($class));
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