<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Application\Service\DecayRate;
use App\Application\Service\MarketPhaseService;
use App\Application\Service\VerificationMailSender;
use App\Application\UseCase\Auth\LoginUser;
use App\Application\UseCase\Auth\RegisterUser;
use App\Application\UseCase\Auth\VerifyEmail;
use App\Application\UseCase\Invest\InvestInPost;
use App\Application\UseCase\Ranking\RankingQuery;
use App\Application\UseCase\Thread\CreateThread;
use App\Application\UseCase\Thread\PostReply;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\PdoEmailVerificationRepository;
use App\Infrastructure\Persistence\PdoHoldingRepository;
use App\Infrastructure\Persistence\PdoInvestmentRepository;
use App\Infrastructure\Persistence\PdoPostRepository;
use App\Infrastructure\Persistence\PdoThreadRepository;
use App\Infrastructure\Persistence\PdoTransactionManager;
use App\Infrastructure\Persistence\PdoUserRepository;
use App\Infrastructure\Persistence\PdoWorldStateRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Fake\FakeMailer;

/**
 * 実DB（MySQL）を通す結合テスト。実SQL（IN一括取得・新カラム・トランザクション）を検証する。
 * RUN_DB_TESTS=1 かつ接続可能なときのみ実行（CIで MySQL サービスを用意して走らせる）。
 */
final class DatabaseFlowTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        if (getenv('RUN_DB_TESTS') !== '1') {
            self::markTestSkipped('RUN_DB_TESTS=1 のときのみ実行します');
        }
        try {
            $this->pdo = Database::connect();
        } catch (\Throwable $e) {
            self::markTestSkipped('DBへ接続できません: ' . $e->getMessage());
        }

        // クリーンスレート。シードしたボット（is_bot=1）は残し、人間と取引データのみ消す。
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['investments', 'holdings', 'email_verifications', 'posts', 'threads'] as $t) {
            $this->pdo->exec("TRUNCATE TABLE {$t}");
        }
        $this->pdo->exec('DELETE FROM users WHERE is_bot = 0');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    public function testFullRegisterVerifyLoginPostInvestRankingFlow(): void
    {
        $users         = new PdoUserRepository($this->pdo);
        $threads       = new PdoThreadRepository($this->pdo);
        $posts         = new PdoPostRepository($this->pdo);
        $holdings      = new PdoHoldingRepository($this->pdo);
        $investments   = new PdoInvestmentRepository($this->pdo);
        $verifications  = new PdoEmailVerificationRepository($this->pdo);
        $worldStates   = new PdoWorldStateRepository($this->pdo);
        $tx            = new PdoTransactionManager($this->pdo);

        $market = new MarketPhaseService($worldStates);
        $decay  = new DecayRate($market, $users);
        $mailer = new FakeMailer();
        $sender = new VerificationMailSender($verifications, $mailer, 'http://test.local');

        $register = new RegisterUser($tx, $users, $sender, $mailer);
        $verify   = new VerifyEmail($tx, $users, $verifications);
        $login    = new LoginUser($users);
        $create   = new CreateThread($threads);
        $reply    = new PostReply($tx, $decay, $threads, $posts);
        $invest   = new InvestInPost($tx, $decay, $posts, $threads, $users, $holdings, $investments);
        $ranking  = new RankingQuery($decay, $users, $holdings, $posts);

        // 1) 登録（未確認）→ メールにトークン
        $register->execute('investor@example.com', '目利き', 'password1');
        $token = $mailer->lastToken();
        self::assertNotNull($token);

        // 2) 確認 → ログイン可能に
        $user = $verify->execute($token);
        self::assertTrue($user->isEmailVerified());

        // 3) ログイン
        $loggedIn = $login->execute('investor@example.com', 'password1');
        self::assertSame($user->id, $loggedIn->id);

        // 4) スレ作成 → レス投稿
        $threadId = $create->execute($user->id, '宇宙人を見た');
        $reply->execute($threadId, hash('sha256', '1.2.3.4'), $user->id, 'これはガチ');
        $alive = $posts->findAliveByThread($threadId);
        self::assertCount(1, $alive);
        $postId = $alive[0]->id;

        // 5) 投資（株価¥10スタート） → 株取得
        $result = $invest->execute($user->id, $postId, 100);
        self::assertGreaterThan(0, $result->shares);
        self::assertSame($result->shares, $holdings->find($user->id, $postId)->shares());

        // 6) ランキング（IN一括取得経路）に本人が総資産付きで現れる
        $rows = $ranking->execute();
        $me = null;
        foreach ($rows as $r) {
            if ($r['name'] === '目利き') {
                $me = $r;
                break;
            }
        }
        self::assertNotNull($me);
        self::assertSame($me['money'] + $me['shareValue'], $me['total']);
        self::assertGreaterThan(0, $me['shareValue']); // 保有株が評価額に反映
    }
}
