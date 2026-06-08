<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invest;

use App\Application\Exception\InvestException;
use App\Application\Port\Logger;
use App\Application\Port\TransactionManager;
use App\Application\Service\DecayRate;
use App\Domain\Repository\HoldingRepository;
use App\Domain\Repository\PostRepository;
use App\Domain\Repository\UserRepository;
use DateTimeImmutable;

/**
 * 株の売却。投稿のリザーブから現金を払い戻す（鮮度で減額）。経済の循環フローの出口。
 * 払い戻し＝リザーブの持ち分按分 × 鮮度（朽ちるほど・dead で 0）。全工程を単一トランザクションで。
 *
 * @phpstan-type SellResult array{shares:int,payout:int,remaining:int}
 */
final class SellShares
{
    public function __construct(
        private readonly TransactionManager $tx,
        private readonly DecayRate $decay,
        private readonly PostRepository $posts,
        private readonly UserRepository $users,
        private readonly HoldingRepository $holdings,
        private readonly ?Logger $logger = null,
    ) {}

    /** @return array{shares:int,payout:int,remaining:int} */
    public function execute(string $sellerId, string $postId, int $shares, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();

        if ($shares < 1) {
            throw InvestException::notEnoughShares();
        }

        return $this->tx->run(function () use ($sellerId, $postId, $shares, $now): array {
            // 残高更新の競合を防ぐため売り手をロック。
            $seller = $this->users->findByIdForUpdate($sellerId);
            if ($seller === null) {
                throw InvestException::notFound();
            }

            $holding = $this->holdings->find($sellerId, $postId);
            if ($holding === null || $holding->shares() < $shares) {
                throw InvestException::notEnoughShares();
            }

            $post = $this->posts->findByIdForUpdate($postId);
            if ($post === null) {
                throw InvestException::notFound();
            }

            $multiplier = $this->decay->multiplier($now);
            $post->settleDecay($now, $multiplier); // 朽ち確定（鮮度を現時点に）

            $payout = $post->sell($shares, $now, $multiplier);

            $seller->credit($payout);
            $holding->removeShares($shares);

            $this->users->save($seller);
            $this->holdings->save($holding);
            $this->posts->save($post);

            $this->logger?->event('shares_sold', [
                'seller_id' => $sellerId,
                'post_id'   => $postId,
                'shares'    => $shares,
                'payout'    => $payout,
            ]);

            return ['shares' => $shares, 'payout' => $payout, 'remaining' => $holding->shares()];
        });
    }
}
