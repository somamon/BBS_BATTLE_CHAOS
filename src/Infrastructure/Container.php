<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Application\Port\Mailer;
use App\Application\Port\RateLimiter;
use App\Application\Port\TransactionManager;
use App\Application\Service\VerificationMailSender;
use App\Domain\Repository\BotSimStateRepository;
use App\Domain\Repository\EmailVerificationRepository;
use App\Domain\Repository\HoldingRepository;
use App\Domain\Repository\InvestmentRepository;
use App\Domain\Repository\PostRepository;
use App\Domain\Repository\ThreadRepository;
use App\Domain\Repository\UserRepository;
use App\Domain\Repository\WorldStateRepository;
use App\Infrastructure\Mail\LogMailer;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\PdoBotSimStateRepository;
use App\Infrastructure\Persistence\PdoEmailVerificationRepository;
use App\Infrastructure\Persistence\PdoHoldingRepository;
use App\Infrastructure\Persistence\PdoInvestmentRepository;
use App\Infrastructure\Persistence\PdoPostRepository;
use App\Infrastructure\Persistence\PdoThreadRepository;
use App\Infrastructure\Persistence\PdoTransactionManager;
use App\Infrastructure\Persistence\PdoUserRepository;
use App\Infrastructure\Persistence\PdoWorldStateRepository;
use App\Infrastructure\RateLimit\PdoRateLimiter;
use DI\ContainerBuilder;
use PDO;
use Psr\Container\ContainerInterface;

use function DI\autowire;
use function DI\create;
use function DI\get;

/**
 * PHP-DI コンテナの構築。
 * UseCase・Service・Controller は autowiring で解決し、
 * PDO とリポジトリ interface→実装の対応だけを明示的に束ねる。
 */
final class Container
{
    public static function build(): ContainerInterface
    {
        $builder = new ContainerBuilder();
        $builder->useAutowiring(true);
        $builder->addDefinitions([
            // 1接続を全リポジトリ・TransactionManager で共有し原子性を担保する
            PDO::class => fn(): PDO => Database::connect(),

            // interface → 実装（autowire で PDO が注入される）
            UserRepository::class              => autowire(PdoUserRepository::class),
            ThreadRepository::class            => autowire(PdoThreadRepository::class),
            PostRepository::class              => autowire(PdoPostRepository::class),
            HoldingRepository::class           => autowire(PdoHoldingRepository::class),
            InvestmentRepository::class        => autowire(PdoInvestmentRepository::class),
            WorldStateRepository::class        => autowire(PdoWorldStateRepository::class),
            EmailVerificationRepository::class => autowire(PdoEmailVerificationRepository::class),
            BotSimStateRepository::class       => autowire(PdoBotSimStateRepository::class),
            TransactionManager::class          => autowire(PdoTransactionManager::class),
            RateLimiter::class                 => autowire(PdoRateLimiter::class),

            // 開発用メーラー（本番は SMTP 実装へ差し替え）。送信内容は var/mail.log へ。
            Mailer::class => create(LogMailer::class)
                ->constructor(\dirname(__DIR__, 2) . '/var/mail.log'),

            // 確認リンクの絶対URL生成に使うベースURL（env: APP_URL）。
            'app.url' => fn(): string => getenv('APP_URL')
                ?: ('http://localhost:' . (getenv('NGINX_PORT') ?: '8080')),

            // 確認メール送信サービス（RegisterUser / ResendVerification が共用）。
            VerificationMailSender::class => autowire()->constructorParameter('appUrl', get('app.url')),
        ]);

        return $builder->build();
    }
}
