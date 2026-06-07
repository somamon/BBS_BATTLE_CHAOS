<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\UseCase\Invest\InvestInPost;
use App\Application\UseCase\Thread\CreateThread;
use App\Application\UseCase\Thread\PostReply;
use App\Config\Game;
use App\Domain\Entity\Post;
use App\Domain\Entity\User;
use App\Domain\Repository\BotSimStateRepository;
use App\Domain\Repository\PostRepository;
use App\Domain\Repository\ThreadRepository;
use App\Domain\Repository\UserRepository;
use DateTimeImmutable;

/**
 * NPC投資家シミュレーション。ソロ/少人数でも相場が動くよう、時間経過に応じて
 * ボットが投稿へ投資し、たまにレス・スレ立てをする。cron 不要の遅延tick方式。
 *
 * 稼働条件: 人間ユーザーが {@see Game::BOT_MAX_HUMANS} 人以下のときのみ。
 * 経済ルールは既存ユースケース（InvestInPost / PostReply / CreateThread）を再利用する。
 */
final class MarketSimulator
{
    private const THREAD_TITLES = [
        'この投稿、伸びると思う', '今日の相場どうよ', '名作レス発掘スレ', '雑談スレ',
        '急騰しそうな投稿まとめ', '初心者だけど質問', '底値拾いたい', '殿堂入り目指すスレ',
    ];

    private const REPLY_LINES = [
        'これは伸びる', '草', 'なるほど', '目利き案件', 'buy', 'ここ底値だろ',
        'ファンダ良し', '提灯点灯', '気になる', 'ガチホ安定', '今のうちに仕込む', '様子見',
    ];

    public function __construct(
        private readonly BotSimStateRepository $simState,
        private readonly UserRepository $users,
        private readonly ThreadRepository $threads,
        private readonly PostRepository $posts,
        private readonly InvestInPost $invest,
        private readonly PostReply $postReply,
        private readonly CreateThread $createThread,
    ) {}

    /** 経過時間に応じてボットのアクションを実行する。例外は飲み込み、画面表示を妨げない。 */
    public function tick(?DateTimeImmutable $now = null): void
    {
        $now ??= new DateTimeImmutable();

        // 人間が増えたら休眠。クロックは進めて復帰時の一括実行を防ぐ。
        if ($this->users->countHumans() > Game::BOT_MAX_HUMANS) {
            $this->simState->setLastTick($now);
            return;
        }

        $elapsed = $now->getTimestamp() - $this->simState->getLastTick()->getTimestamp();
        if ($elapsed <= 0) {
            return;
        }

        $actions = (int) min(Game::BOT_MAX_BURST, intdiv($elapsed, Game::BOT_TICK_SECONDS));
        if ($actions <= 0) {
            return;
        }

        $bots = $this->users->bots();
        if ($bots === []) {
            $this->simState->setLastTick($now);
            return;
        }

        for ($i = 0; $i < $actions; $i++) {
            try {
                $this->act($bots, $now);
            } catch (\Throwable) {
                // 1アクションの失敗（dead/残高不足等）は無視して次へ。
            }
        }

        $this->simState->setLastTick($now);
    }

    /** @param User[] $bots */
    private function act(array $bots, DateTimeImmutable $now): void
    {
        $bot = $bots[random_int(0, count($bots) - 1)];

        $aliveThreads = $this->threads->findAlive(50);
        if ($aliveThreads === []) {
            // 板が無ければまず立てる。
            $this->createThread->execute($bot->id, $this->pick(self::THREAD_TITLES));
            return;
        }

        $alivePosts = $this->posts->findAlive(100);
        $roll = random_int(1, 100);

        // 投資対象があり、ボットに資金があれば 70% で投資。
        if ($alivePosts !== [] && $roll <= 70 && $bot->canAfford(Game::BOT_MIN_INVEST)) {
            $post   = $this->pickWeighted($alivePosts);
            $budget = min($bot->money(), Game::BOT_MAX_INVEST);
            $amount = random_int(Game::BOT_MIN_INVEST, max(Game::BOT_MIN_INVEST, $budget));
            $this->invest->execute($bot->id, $post->id, $amount, $now);
            return;
        }

        // 25%（または投資できない時）はレスを書いて投資対象を増やす。
        if ($roll <= 95 || $alivePosts === []) {
            $thread = $aliveThreads[random_int(0, count($aliveThreads) - 1)];
            $hash   = hash('sha256', 'bot:' . $bot->id);
            $this->postReply->execute($thread->id, $hash, $bot->id, $this->pick(self::REPLY_LINES));
            return;
        }

        // 残りは新スレ。
        $this->createThread->execute($bot->id, $this->pick(self::THREAD_TITLES));
    }

    /**
     * 投稿を「ハイプ（id由来の固定値）× 勢い（累計投資）」で重み付けして1件選ぶ。
     * これにより、人気が出始めた投稿に資金が集まりやすくなる（早期投資の含み益を生む）。
     *
     * @param Post[] $posts
     */
    private function pickWeighted(array $posts): Post
    {
        $weights = [];
        $total = 0.0;
        foreach ($posts as $post) {
            $hype     = (abs(crc32($post->id)) % 100) / 100.0 + 0.2; // 0.2〜1.2（投稿ごとに固定）
            $momentum = 1.0 + $post->totalInvested() / 200.0;        // 集まるほど選ばれやすい
            $weight   = $hype * $momentum;
            $weights[] = $weight;
            $total += $weight;
        }

        $r = random_int(0, 1_000_000) / 1_000_000 * $total;
        foreach ($posts as $i => $post) {
            $r -= $weights[$i];
            if ($r <= 0) {
                return $post;
            }
        }
        return $posts[array_key_last($posts)];
    }

    /** @param string[] $pool */
    private function pick(array $pool): string
    {
        return $pool[random_int(0, count($pool) - 1)];
    }
}
