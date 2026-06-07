<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

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

    public function __construct(
        private readonly MarketPhaseService $market,
        private readonly Auth $auth,
        private readonly UserRepository $users,
        private readonly ListThreads $listThreads,
        private readonly ListDeadThreads $listDeadThreads,
        private readonly ShowThread $showThread,
        private readonly CreateThread $createThread,
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
        try {
            $id = $this->createThread->execute($this->auth->userId(), $title, current_locale());
        } catch (ValidationException $e) {
            $html = $this->page($this->market, $this->auth, $this->users, t('thread_create.title'), 'Thread/create', [
                'error' => t($e->messageKey),
                'title' => $title,
            ]);
            return Response::html($html, 422);
        }
        return Response::redirect('/thread/' . $id);
    }
}
