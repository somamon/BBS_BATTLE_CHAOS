<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Port\RateLimiter;
use App\Domain\Repository\BanRepository;
use App\Application\Service\MarketPhaseService;
use App\Application\UseCase\Thread\CreateThread;
use App\Application\UseCase\Thread\ListDeadThreads;
use App\Application\UseCase\Thread\ListThreads;
use App\Application\UseCase\Thread\ShowThread;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\UserRepository;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Flash;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

final class ThreadController
{
    use RendersLayout;

    /** スレ立ての連投クールダウン（秒）と時間あたり上限。 */
    private const COOLDOWN = 30;
    private const MAX    = 10;
    private const WINDOW = 3600; // 1時間

    public function __construct(
        private readonly MarketPhaseService $market,
        private readonly Auth $auth,
        private readonly UserRepository $users,
        private readonly ListThreads $listThreads,
        private readonly ListDeadThreads $listDeadThreads,
        private readonly ShowThread $showThread,
        private readonly CreateThread $createThread,
        private readonly RateLimiter $rateLimiter,
        private readonly BanRepository $bans,
    ) {}

    /** GET /threads スレッド一覧（?page=N） */
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $data = $this->listThreads->execute(current_locale(), $page);

        $html = $this->page($this->market, $this->auth, $this->users, t('nav.threads'), 'Thread/index', [
            'threads'    => $data['items'],
            'page'       => $data['page'],
            'totalPages' => $data['totalPages'],
        ]);
        return Response::html($html);
    }

    /** GET /threads/dead 墓場（朽ちたスレのタイトル一覧） */
    public function dead(Request $request): Response
    {
        $html = $this->page($this->market, $this->auth, $this->users, t('dead.title'), 'Thread/dead', [
            'threads' => $this->listDeadThreads->execute(current_locale()),
        ]);
        return Response::html($html);
    }

    /** GET /thread/create スレッド作成フォーム */
    public function createForm(Request $request): Response
    {
        $html = $this->page($this->market, $this->auth, $this->users, t('thread_create.title'), 'Thread/create', [
            'error' => null,
            'title' => '',
        ]);
        return Response::html($html);
    }

    /** GET /thread/{id} スレッド詳細 + レス */
    public function show(Request $request): Response
    {
        $id   = (string) $request->param('id');
        $data = $this->showThread->execute($id, $this->auth->userId());
        if ($data === null) {
            return Response::error(404, t('err.thread_not_found'));
        }

        $html = $this->page($this->market, $this->auth, $this->users, $data['thread']['title'], 'Thread/show', [
            'thread'  => $data['thread'],
            'posts'   => $data['posts'],
            'isLogin' => $this->auth->check(),
            'flash'   => Flash::pull(),
        ]);
        return Response::html($html);
    }

    /** POST /threads スレッド作成 */
    public function create(Request $request): Response
    {
        $title = (string) $request->input('title', '');
        $ip    = $request->ip();

        // IP BAN チェック。
        if ($this->bans->isBanned('ip', \App\Infrastructure\Security\IpHash::of($ip))) {
            $html = $this->page($this->market, $this->auth, $this->users, t('thread_create.title'), 'Thread/create', [
                'error' => t('err.banned'),
                'title' => $title,
            ]);
            return Response::html($html, 403);
        }

        // 凍結(suspend/ban)中のログインユーザーはスレ作成不可（IP-BANだけでは防げない）。
        $uid = $this->auth->userId();
        if ($uid !== null) {
            $actor = $this->users->findById($uid);
            if ($actor !== null && !$actor->isActive()) {
                $html = $this->page($this->market, $this->auth, $this->users, t('thread_create.title'), 'Thread/create', [
                    'error' => t('err.banned'),
                    'title' => $title,
                ]);
                return Response::html($html, 403);
            }
        }

        // 連投規制（クールダウン）と時間あたり上限。NPCはこのコントローラを通らないので対象外。
        if (
            $this->rateLimiter->tooManyAttempts('thread_cd:' . $ip, 1)
            || $this->rateLimiter->tooManyAttempts('thread:' . $ip, self::MAX)
        ) {
            $msg = $this->rateLimiter->tooManyAttempts('thread_cd:' . $ip, 1)
                ? t('err.posting_too_fast')
                : t('err.too_many_attempts');
            $html = $this->page($this->market, $this->auth, $this->users, t('thread_create.title'), 'Thread/create', [
                'error' => $msg,
                'title' => $title,
            ]);
            return Response::html($html, 429);
        }

        try {
            $id = $this->createThread->execute($this->auth->userId(), $title, current_locale());
        } catch (ValidationException $e) {
            $html = $this->page($this->market, $this->auth, $this->users, t('thread_create.title'), 'Thread/create', [
                'error' => t($e->messageKey),
                'title' => $title,
            ]);
            return Response::html($html, 422);
        }

        $this->rateLimiter->hit('thread_cd:' . $ip, self::COOLDOWN);
        $this->rateLimiter->hit('thread:' . $ip, self::WINDOW);

        return Response::redirect('/thread/' . $id);
    }
}
