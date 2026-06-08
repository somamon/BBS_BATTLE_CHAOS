<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Application\Port\AuditLogger;
use App\Application\Port\GameStateResetter;
use App\Application\Port\Logger;
use App\Application\Port\Mailer;
use App\Application\Port\RateLimiter;
use App\Application\Port\TransactionManager;
use App\Config\Environment;
use App\Infrastructure\Audit\PdoAuditLogger;
use App\Infrastructure\Auth\GoogleOAuth;
use App\Infrastructure\Logging\JsonLogger;
use App\Application\Service\PasswordResetMailSender;
use App\Application\Service\VerificationMailSender;
use App\Application\UseCase\Contact\SubmitContact;
use App\Domain\Repository\BotSimStateRepository;
use App\Domain\Repository\EmailVerificationRepository;
use App\Domain\Repository\PasswordResetRepository;
use App\Domain\Repository\HoldingRepository;
use App\Domain\Repository\InvestmentRepository;
use App\Domain\Repository\PostRepository;
use App\Domain\Repository\AuditLogRepository;
use App\Domain\Repository\BanRepository;
use App\Domain\Repository\ContactMessageRepository;
use App\Domain\Repository\ReportRepository;
use App\Domain\Repository\SettingRepository;
use App\Domain\Repository\RoundRepository;
use App\Domain\Repository\ThreadRepository;
use App\Domain\Repository\UserRepository;
use App\Domain\Repository\WorldStateRepository;
use App\Infrastructure\Mail\LogMailer;
use App\Infrastructure\Mail\SmtpMailer;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\PdoBotSimStateRepository;
use App\Infrastructure\Persistence\PdoEmailVerificationRepository;
use App\Infrastructure\Persistence\PdoHoldingRepository;
use App\Infrastructure\Persistence\PdoInvestmentRepository;
use App\Infrastructure\Persistence\PdoGameStateResetter;
use App\Infrastructure\Persistence\PdoPasswordResetRepository;
use App\Infrastructure\Persistence\PdoPostRepository;
use App\Infrastructure\Persistence\PdoAuditLogRepository;
use App\Infrastructure\Persistence\PdoBanRepository;
use App\Infrastructure\Persistence\PdoContactMessageRepository;
use App\Infrastructure\Persistence\PdoReportRepository;
use App\Infrastructure\Persistence\PdoSettingRepository;
use App\Infrastructure\Persistence\PdoRoundRepository;
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

            // 構造化ログ（M4）。相関IDは RequestContext から自動付与される。
            Logger::class => fn(): Logger => new JsonLogger(Environment::appEnv()),

            // 監査ログ（管理操作の記録）。
            AuditLogger::class => autowire(PdoAuditLogger::class),

            // interface → 実装（autowire で PDO が注入される）
            UserRepository::class              => autowire(PdoUserRepository::class),
            ThreadRepository::class            => autowire(PdoThreadRepository::class),
            PostRepository::class              => autowire(PdoPostRepository::class),
            HoldingRepository::class           => autowire(PdoHoldingRepository::class),
            InvestmentRepository::class        => autowire(PdoInvestmentRepository::class),
            WorldStateRepository::class        => autowire(PdoWorldStateRepository::class),
            EmailVerificationRepository::class => autowire(PdoEmailVerificationRepository::class),
            PasswordResetRepository::class     => autowire(PdoPasswordResetRepository::class),
            RoundRepository::class             => autowire(PdoRoundRepository::class),
            ReportRepository::class            => autowire(PdoReportRepository::class),
            BanRepository::class               => autowire(PdoBanRepository::class),
            ContactMessageRepository::class    => autowire(PdoContactMessageRepository::class),
            SettingRepository::class           => autowire(PdoSettingRepository::class),
            AuditLogRepository::class          => autowire(PdoAuditLogRepository::class),
            BotSimStateRepository::class       => autowire(PdoBotSimStateRepository::class),
            TransactionManager::class          => autowire(PdoTransactionManager::class),
            RateLimiter::class                 => autowire(PdoRateLimiter::class),
            GameStateResetter::class           => autowire(PdoGameStateResetter::class),

            // メーラー：MAIL_DRIVER で切替（smtp=実送信/Mailpit、log=ファイル出力）。
            Mailer::class => function (): Mailer {
                if ((getenv('MAIL_DRIVER') ?: 'log') === 'smtp') {
                    return new SmtpMailer(
                        host: getenv('MAIL_HOST') ?: 'localhost',
                        port: (int) (getenv('MAIL_PORT') ?: 1025),
                        username: getenv('MAIL_USERNAME') ?: '',
                        password: getenv('MAIL_PASSWORD') ?: '',
                        encryption: getenv('MAIL_ENCRYPTION') ?: 'none',
                        fromAddress: getenv('MAIL_FROM') ?: 'no-reply@example.com',
                        fromName: getenv('MAIL_FROM_NAME') ?: 'BBS BATTLE CHAOS',
                    );
                }
                return new LogMailer(\dirname(__DIR__, 2) . '/var/mail.log');
            },

            // 確認リンクの絶対URL生成に使うベースURL（env: APP_URL）。
            'app.url' => fn(): string => getenv('APP_URL')
                ?: ('http://localhost:' . (getenv('NGINX_PORT') ?: '8080')),

            // Googleログイン（OAuth/OIDC）。未設定（CLIENT_ID/SECRET空）ならボタンも導線も無効。
            GoogleOAuth::class => function (ContainerInterface $c): GoogleOAuth {
                $redirect = getenv('GOOGLE_REDIRECT_URL')
                    ?: (rtrim((string) $c->get('app.url'), '/') . '/auth/google/callback');
                return new GoogleOAuth(
                    clientId: getenv('GOOGLE_CLIENT_ID') ?: '',
                    clientSecret: getenv('GOOGLE_CLIENT_SECRET') ?: '',
                    redirectUrl: $redirect,
                );
            },

            // 確認メール送信サービス（RegisterUser / ResendVerification が共用）。
            VerificationMailSender::class => autowire()->constructorParameter('appUrl', get('app.url')),

            // パスワード再設定メール送信サービス（RequestPasswordReset が使う）。
            PasswordResetMailSender::class => autowire()->constructorParameter('appUrl', get('app.url')),

            // お問い合わせの宛先（法務ページの連絡先と同じ。LEGAL_CONTACT で上書き可）。
            'contact.to' => fn(): string => getenv('LEGAL_CONTACT') ?: '8556iamsmartphone0124@gmail.com',
            SubmitContact::class => autowire()->constructorParameter('contactTo', get('contact.to')),
        ]);

        return $builder->build();
    }
}
