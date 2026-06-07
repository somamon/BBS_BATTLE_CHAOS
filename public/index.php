<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Config\Environment;
use App\Infrastructure\Container;
use App\Presentation\I18n\Translator;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;
use App\Presentation\Routing\Router;
use App\Presentation\Routing\NotFoundException;
use App\Presentation\Routing\MethodNotAllowedException;

// アプリ全体のタイムゾーン（表示・保存の基準）。MySQL セッションも Database で合わせる。
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Tokyo');

// 表示言語の解決：Cookie 'lang' を優先、無ければ Accept-Language、既定は日本語。
$lang = $_COOKIE['lang'] ?? null;
if (!is_string($lang) || !in_array($lang, Translator::SUPPORTED, true)) {
    $accept = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
    $lang = str_starts_with($accept, 'en') ? 'en' : Translator::DEFAULT;
}
Translator::activate(Translator::for($lang));

// 本番で危険な既定値のまま起動しないよう検証（不足なら 500 で停止・詳細は漏らさない）。
try {
    Environment::assertProductionReady();
} catch (\Throwable $e) {
    error_log('[boot] ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<h1>500</h1><p>設定エラーにより起動できません。管理者にお問い合わせください。</p>';
    exit;
}

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

// NPC投資家の遅延シミュレーション（人間50人以下のときだけ稼働。cron不要）。
// 画面表示を妨げないよう GET のみ・例外は握りつぶす。
if ($request->method() === 'GET') {
    try {
        $container->get(App\Application\Service\MarketSimulator::class)->tick(new DateTimeImmutable());
    } catch (\Throwable $e) {
        error_log('[sim] ' . $e);
    }
}

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