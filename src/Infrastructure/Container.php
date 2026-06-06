<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Application\Port\TransactionManager;
use App\Domain\Repository\HoldingRepository;
use App\Domain\Repository\InvestmentRepository;
use App\Domain\Repository\PostRepository;
use App\Domain\Repository\ThreadRepository;
use App\Domain\Repository\UserRepository;
use App\Domain\Repository\WorldStateRepository;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\PdoHoldingRepository;
use App\Infrastructure\Persistence\PdoInvestmentRepository;
use App\Infrastructure\Persistence\PdoPostRepository;
use App\Infrastructure\Persistence\PdoThreadRepository;
use App\Infrastructure\Persistence\PdoTransactionManager;
use App\Infrastructure\Persistence\PdoUserRepository;
use App\Infrastructure\Persistence\PdoWorldStateRepository;
use DI\ContainerBuilder;
use PDO;
use Psr\Container\ContainerInterface;

use function DI\autowire;

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
            UserRepository::class       => autowire(PdoUserRepository::class),
            ThreadRepository::class     => autowire(PdoThreadRepository::class),
            PostRepository::class       => autowire(PdoPostRepository::class),
            HoldingRepository::class    => autowire(PdoHoldingRepository::class),
            InvestmentRepository::class => autowire(PdoInvestmentRepository::class),
            WorldStateRepository::class => autowire(PdoWorldStateRepository::class),
            TransactionManager::class   => autowire(PdoTransactionManager::class),
        ]);

        return $builder->build();
    }
}
