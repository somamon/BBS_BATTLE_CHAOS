<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Config\Environment;
use App\Infrastructure\Container;
use App\Infrastructure\Logging\JsonLogger;
use App\Infrastructure\Logging\RequestContext;
use App\Presentation\I18n\Translator;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;
use App\Presentation\Routing\Router;
use App\Presentation\Routing\NotFoundException;
use App\Presentation\Routing\MethodNotAllowedException;

// アプリ全体のタイムゾーン（表示・保存の基準）。MySQL セッションも Database で合わせる。
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Tokyo');

// 相関ID（リクエストID）を確定。上流（LB/プロキシ）が付けていれば引き継ぐ。M4。
$requestId = RequestContext::init($_SERVER['HTTP_X_REQUEST_ID'] ?? null);
$logger    = new JsonLogger(Environment::appEnv());

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
    $logger->error('boot_failed', ['error' => $e->getMessage()]);
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

// ランタイム設定（管理画面の settings）を読み込み、ゲームバランス上書きとサイト状態へ反映。
// 取得失敗（マイグレーション前など）は既定値で続行する。
try {
    $settings = $container->get(App\Domain\Repository\SettingRepository::class)->all();
    App\Config\Game::applyOverrides($settings);
    App\Infrastructure\Runtime\SiteState::boot($settings);
} catch (\Throwable $e) {
    $logger->warning('settings_load_failed', ['error' => $e->getMessage()]);
}

// メンテモード：管理画面・ログイン以外は 503 で停止（運営は /admin で操作継続）。
if (
    App\Infrastructure\Runtime\SiteState::isMaintenance()
    && !str_starts_with($request->path(), '/admin')
    && !in_array($request->path(), ['/login', '/logout'], true)
) {
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    header('Retry-After: 3600');
    echo '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>メンテナンス中</title></head>'
        . '<body style="font-family:sans-serif; text-align:center; padding:40px; color:#333;">'
        . '<h1>メンテナンス中</h1><p>ただいまメンテナンス中です。しばらくお待ちください。</p></body></html>';
    exit;
}

// ゲーム進行（NPCシミュレーション＋終局時の自動リセット）。
// 既定は Web フォールバック方式（公開GETに相乗り、cron不要）。GAME_TICK_DRIVER=cron なら
// 進行は bin/cron.php に任せ、ここでは回さない。画面表示を妨げないよう GET のみ・例外は内部で握りつぶす。
if ($request->method() === 'GET' && (getenv('GAME_TICK_DRIVER') ?: 'web') !== 'cron') {
    $container->get(App\Application\Service\GameTick::class)->run(new DateTimeImmutable());
}

// シーズン終了予定時刻をレイアウトのカウントダウンへ供給（GameTick の後に取り、リセット直後の新ラウンドを反映）。
// 時間制オフ（期間0以下）やラウンド未取得・取得失敗時は非表示のまま続行する。
try {
    $duration = App\Config\Game::seasonDurationSec();
    if ($duration > 0) {
        $round = $container->get(App\Domain\Repository\RoundRepository::class)->current();
        if ($round !== null) {
            App\Infrastructure\Runtime\SeasonState::boot($round->startedAt->modify("+{$duration} seconds"));
        }
    }
} catch (\Throwable $e) {
    $logger->warning('season_state_boot_failed', ['error' => $e->getMessage()]);
}

try {
    $response = $router->dispatch($request);
} catch (NotFoundException) {
    $response = Response::error(404, 'Not Found');
} catch (MethodNotAllowedException $e) {
    $response = Response::error(405, 'Method Not Allowed')
        ->withHeader('Allow', implode(', ', $e->allowed));
} catch (\Throwable $e) {
    $logger->error('unhandled_exception', [
        'exception' => $e::class,
        'message'   => $e->getMessage(),
        'where'     => $e->getFile() . ':' . $e->getLine(),
    ]);

    $message = Environment::appEnv() === 'development'
        ? (string) $e
        : 'Internal Server Error';

    $response = Response::error(500, $message);
}

// 相関IDをレスポンスにも出し、クライアント側の問い合わせと突き合わせられるようにする。
$response->withHeader('X-Request-Id', $requestId);

// アクセスログ（主要KPI：エラー率は status と組み合わせて算出できる）。
$logger->event('request', [
    'method'      => $request->method(),
    'path'        => $request->path(),
    'status'      => $response->status(),
    'duration_ms' => RequestContext::elapsedMs(),
]);

$response->send();