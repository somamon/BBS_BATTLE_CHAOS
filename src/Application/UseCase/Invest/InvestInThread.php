<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invest;

use App\Application\Exception\InvestException;
use App\Application\Port\TransactionManager;
use App\Application\Service\MarketPhaseService;
use App\Config\Game;
use App\Domain\Entity\Holding;
use App\Domain\Entity\Investment;
use App\Domain\Repository\HoldingRepository;
use App\Domain\Repository\InvestmentRepository;
use App\Domain\Repository\ThreadRepository;
use App\Domain\Repository\UserRepository;
use DateTimeImmutable;

/**
 * 投資（株式モデル）— 経済の心臓（docs/design/20 §2.2 / §2.6）。
 *
 * 投資額を HP回復50% / 既存株主への配当30%（変異ボーナス込み）/ 消滅20% に分け、
 * 投資者へ amount 株を発行する。全工程を単一トランザクションで原子的に行う。
 */
final class InvestInThread
{
    public function __construct(
        private readonly TransactionManager $tx,
        private readonly MarketPhaseService $market,
        private readonly ThreadRepository $threads,
        private readonly UserRepository $users,
        private readonly HoldingRepository $holdings,
        private readonly InvestmentRepository $investments,
    ) {}

    public function execute(string $investorId, string $threadId, int $amount, ?DateTimeImmutable $now = null): InvestResult
    {
        $now ??= new DateTimeImmutable();

        if ($amount < Game::MIN_INVEST) {
            throw InvestException::invalidAmount();
        }

        return $this->tx->run(function () use ($investorId, $threadId, $amount, $now): InvestResult {
            $investor = $this->users->findById($investorId);
            if ($investor === null) {
                throw InvestException::insufficientFunds();
            }
            if (!$investor->canAfford($amount)) {
                throw InvestException::insufficientFunds();
            }

            $thread = $this->threads->findByIdForUpdate($threadId);
            if ($thread === null) {
                throw InvestException::notFound();
            }

            // 現在の相場で減衰を確定。死んでいたら投資不可。
            $multiplier = $this->market->resolve($now)->multiplier();
            $thread->settleDecay($now, $multiplier);
            if (!$thread->isAlive()) {
                $this->threads->save($thread); // 朽ちた事実は確定させる
                throw InvestException::dead();
            }

            $levelBefore = $thread->mutationLevel();
            $totalBefore = $thread->totalShares();

            // 出金
            $investor->debit($amount);

            // HP回復（max_hp 超過分は sink へ）
            $hpPortion = (int) floor($amount * Game::SPLIT_HP);
            $overflow  = $thread->heal($hpPortion, $now);
            $toHp      = $hpPortion - $overflow;

            // 既存株主への配当（新規株発行の前・投資者本人は除外）
            $dividendPool = (int) floor($amount * Game::SPLIT_DIVIDEND * $thread->dividendBonus());
            $distributed  = 0;
            if ($totalBefore > 0 && $dividendPool > 0) {
                foreach ($this->holdings->findByThread($threadId) as $holder) {
                    if ($holder->userId === $investorId || $holder->shares() <= 0) {
                        continue;
                    }
                    $share = (int) floor($dividendPool * $holder->shares() / $totalBefore);
                    if ($share <= 0) {
                        continue;
                    }
                    $holderUser = $this->users->findById($holder->userId);
                    if ($holderUser === null) {
                        continue;
                    }
                    $holderUser->credit($share);
                    $this->users->save($holderUser);
                    $distributed += $share;
                }
            }
            $toDividend = $distributed;
            $toSink     = $amount - $toHp - $toDividend; // 残り（HP超過・配当端数・本人分・素のsink）

            // 投資者へ株を発行（→ ここで変異判定）
            $thread->issueShares($amount, $now);
            $holding = $this->holdings->find($investorId, $threadId) ?? Holding::empty($investorId, $threadId);
            $holding->addShares($amount);
            $this->holdings->save($holding);

            // 永続化
            $this->users->save($investor);
            $this->threads->save($thread);
            $this->investments->insert(Investment::record(
                $investorId, $threadId, $amount, $toHp, $toDividend, $toSink, $now,
            ));

            return new InvestResult(
                amount: $amount,
                toHp: $toHp,
                toDividend: $toDividend,
                toSink: $toSink,
                threadHpAfter: $thread->hp(),
                mutationLevelAfter: $thread->mutationLevel(),
                mutated: $thread->mutationLevel() > $levelBefore,
            );
        });
    }
}
