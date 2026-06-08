<?php

declare(strict_types=1);

/**
 * 管理者ロールの付与/剥奪 CLI（管理画面フェーズ1）。
 *
 * 使い方:
 *   php bin/admin.php promote <email>   # 既存アカウントを admin にする
 *   php bin/admin.php demote  <email>   # admin を user に戻す
 *
 * Web からの権限昇格導線は設けない（昇格の入口を作らない）方針のため、CLI 専用。
 * 操作は監査ログ（admin_audit_logs、admin_id='cli'）に記録される。
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Application\UseCase\Admin\ChangeUserRole;
use App\Domain\Repository\UserRepository;
use App\Infrastructure\Container;
use App\Infrastructure\Logging\RequestContext;

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Tokyo');
RequestContext::init();

$cmd   = $argv[1] ?? '';
$email = $argv[2] ?? '';

$role = match ($cmd) {
    'promote' => 'admin',
    'demote'  => 'user',
    default   => null,
};

if ($role === null || $email === '') {
    fwrite(STDERR, "使い方: php bin/admin.php promote|demote <email>\n");
    exit(1);
}

try {
    $container = Container::build();
    /** @var UserRepository $users */
    $users = $container->get(UserRepository::class);
    /** @var ChangeUserRole $changeRole */
    $changeRole = $container->get(ChangeUserRole::class);

    $user = $users->findByEmail(strtolower(trim($email)));
    if ($user === null) {
        fwrite(STDERR, "[admin] ユーザーが見つかりません: {$email}\n");
        exit(1);
    }

    $changeRole->execute('cli', $user->id, $role);
} catch (\Throwable $e) {
    fwrite(STDERR, '[admin] 失敗: ' . $e->getMessage() . "\n");
    exit(1);
}

fwrite(STDOUT, "[admin] {$email} を role={$role} に設定しました。\n");
exit(0);
