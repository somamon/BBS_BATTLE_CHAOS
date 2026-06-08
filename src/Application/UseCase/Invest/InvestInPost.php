<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invest;

use App\Application\Exception\InvestException;
use App\Application\Port\Logger;
use App\Application\Port\TransactionManager;
use App\Application\Service\DecayRate;
use App\Config\Game;
use App\Domain\Entity\Holding;
use App\Domain\Entity\Investment;
use App\Domain\Repository\HoldingRepository;
use App\Domain\Repository\InvestmentRepository;
use App\Domain\Repository\PostRepository;
use App\Domain\Repository\ThreadRepository;
use App\Domain\Repository\UserRepository;
use DateTimeImmutable;

/**
 * 投資（ボンディングカーブ）— 経済の心臓（doc21 §2.2 / §2.3）。
 *
 * 投資額の 70% で現在株価から株を購入、30% を投稿のHP回復に充当する。配当・sink は無し。
 * 早期ほど株価が安く多く買えるため「目利き」が報われる。全工程を単一トランザクションで原子的に行う。
 */
final class InvestInPost
{
    public function __construct(
        private readonly TransactionManager $tx,
        private readonly DecayRate $decay,
        private readonly PostRepository $posts,
        private readonly ThreadRepository $threads,
        private readonly UserRepository $users,
        private readonly HoldingRepository $holdings,
        private readonly InvestmentRepository $investments,
        private readonly ?Logger $logger = null,
    ) {}

    public function execute(string $investorId, string $postId, int $amount, ?DateTimeImmutable $now = null): InvestResult
    {
        $now ??= new DateTimeImmutable();

        if ($amount < Game::minInvest()) {
            throw InvestException::invalidAmount();
        }

        return $this->tx->run(function () use ($investorId, $postId, $amount, $now): InvestResult {
            $investor = $this->users->findById($investorId);
            if ($investor === null) {
                throw InvestException::insufficientFunds();
            }
            // 凍結/BAN中のアカウントは投資不可（アクティブセッションでの操作も塞ぐ）。
            if (!$investor->isActive()) {
                throw InvestException::accountInactive();
            }
            if (!$investor->canAfford($amount)) {
                throw InvestException::insufficientFunds();
            }

            $post = $this->posts->findByIdForUpdate($postId);
            if ($post === null || $post->isHidden()) {
                throw InvestException::notFound(); // 非表示の投稿には投資できない
            }

            $multiplier = $this->decay->multiplier($now);

            // 投稿の減衰を確定。死んでいたら投資不可。
            $post->settleDecay($now, $multiplier);
            if (!$post->isAlive()) {
                $this->posts->save($post);
                throw InvestException::dead();
            }

            // 親スレ（板）が dead なら配下の投稿も投資不可（カスケード）。
            $thread = $this->threads->findByIdForUpdate($post->threadId);
            if ($thread !== null) {
                $thread->settleDecay($now, $multiplier);
                if (!$thread->isAlive()) {
                    $this->threads->save($thread);
                    throw InvestException::dead();
                }
                $this->threads->save($thread);
            }

            $levelBefore = $post->level();

            // 株購入＋HP回復＋レベル判定（ドメインに委譲）
            $applied = $post->applyInvestment($amount, $now);
            if ($applied['shares'] <= 0) {
                throw InvestException::tooSmall(); // ロールバックされる
            }

            // 出金・持ち分加算
            $investor->debit($amount);
            $holding = $this->holdings->find($investorId, $postId) ?? Holding::empty($investorId, $postId);
            $holding->addShares($applied['shares'], $applied['toShares']); // 取得原価は株購入額（70%）

            // 永続化
            $this->users->save($investor);
            $this->holdings->save($holding);
            $this->posts->save($post);
            $this->investments->insert(Investment::record(
                $investorId,
                $postId,
                $amount,
                $applied['shares'],
                $applied['price'],
                $applied['toShares'],
                $applied['toHp'],
                $now,
            ));

            // KPI: 投資イベント。NPC(bot)分は is_bot で区別できるよう付与する。
            $this->logger?->event('investment_made', [
                'investor_id' => $investorId,
                'post_id'     => $postId,
                'amount'      => $amount,
                'shares'      => $applied['shares'],
                'is_bot'      => $investor->isBot,
            ]);

            return new InvestResult(
                amount: $amount,
                shares: $applied['shares'],
                price: $applied['price'],
                toShares: $applied['toShares'],
                toHp: $applied['toHp'],
                postHpAfter: $post->hp(),
                levelAfter: $post->level(),
                leveledUp: $post->level() > $levelBefore,
            );
        });
    }
}
