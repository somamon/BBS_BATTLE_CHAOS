<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Application\UseCase\Admin\BanIp;
use App\Application\UseCase\Admin\ModeratePost;
use App\Application\UseCase\Admin\ModerateThread;
use App\Domain\Repository\PostRepository;
use App\Domain\Repository\ThreadRepository;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Flash;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

/**
 * コンテンツモデレーション（スレ/レスの非表示・復帰）。
 */
final class ContentController
{
    use RendersAdmin;

    private const PER = 20;

    public function __construct(
        private readonly ThreadRepository $threads,
        private readonly PostRepository $posts,
        private readonly ModerateThread $moderateThread,
        private readonly ModeratePost $moderatePost,
        private readonly BanIp $banIp,
        private readonly Auth $auth,
    ) {}

    /** GET /admin/content?tp=&pp= （スレ・レスを個別にページング） */
    public function index(Request $request): Response
    {
        $tTotal = $this->threads->countForAdmin();
        $pTotal = $this->posts->countForAdmin();
        $tPages = max(1, (int) ceil($tTotal / self::PER));
        $pPages = max(1, (int) ceil($pTotal / self::PER));
        $tp = max(1, min((int) $request->query('tp', 1), $tPages));
        $pp = max(1, min((int) $request->query('pp', 1), $pPages));

        $threads = [];
        foreach ($this->threads->recentForAdmin(self::PER, ($tp - 1) * self::PER) as $t) {
            $threads[] = [
                'id'     => $t->id,
                'title'  => $t->title,
                'lang'   => $t->lang,
                'status' => $t->status(),
                'hidden' => $t->isHidden(),
            ];
        }
        $posts = [];
        foreach ($this->posts->recentForAdmin(self::PER, ($pp - 1) * self::PER) as $p) {
            $posts[] = [
                'id'       => $p->id,
                'threadId' => $p->threadId,
                'excerpt'  => mb_substr($p->content, 0, 60),
                'status'   => $p->status(),
                'hidden'   => $p->isHidden(),
            ];
        }

        return $this->adminPage('content', 'コンテンツ', 'Admin/content', [
            'threads' => $threads,
            'posts'   => $posts,
            'tp'      => $tp,
            'tPages'  => $tPages,
            'pp'      => $pp,
            'pPages'  => $pPages,
            'flash'   => Flash::pull(),
        ]);
    }

    /** POST /admin/threads/{id}/hide */
    public function hideThread(Request $request): Response
    {
        $ok = $this->moderateThread->hide((string) $this->auth->userId(), (string) $request->param('id'), $request->ip());
        Flash::set($ok ? 'スレッドを非表示にしました。' : '対象が見つかりませんでした。');
        return Response::redirect('/admin/content');
    }

    /** POST /admin/threads/{id}/unhide */
    public function unhideThread(Request $request): Response
    {
        $ok = $this->moderateThread->unhide((string) $this->auth->userId(), (string) $request->param('id'), $request->ip());
        Flash::set($ok ? 'スレッドを復帰しました。' : '対象が見つかりませんでした。');
        return Response::redirect('/admin/content');
    }

    /** POST /admin/posts/{id}/hide */
    public function hidePost(Request $request): Response
    {
        $ok = $this->moderatePost->hide((string) $this->auth->userId(), (string) $request->param('id'), $request->ip());
        Flash::set($ok ? 'レスを非表示にしました。' : '対象が見つかりませんでした。');
        return Response::redirect('/admin/content');
    }

    /** POST /admin/posts/{id}/unhide */
    public function unhidePost(Request $request): Response
    {
        $ok = $this->moderatePost->unhide((string) $this->auth->userId(), (string) $request->param('id'), $request->ip());
        Flash::set($ok ? 'レスを復帰しました。' : '対象が見つかりませんでした。');
        return Response::redirect('/admin/content');
    }

    /** POST /admin/posts/{id}/ban レスの投稿者IPをBAN（days=0で無期限） */
    public function banPost(Request $request): Response
    {
        $expiresAt = self::expiryFromDays((int) $request->input('days', 0));
        $ok = $this->banIp->banByPost((string) $this->auth->userId(), (string) $request->param('id'), $request->ip(), $expiresAt);
        Flash::set($ok ? '投稿者IPをBANしました。' : '対象が見つかりませんでした。');
        return Response::redirect('/admin/content');
    }

    /** days>0 なら現在＋日数の期限、0以下なら無期限(null)。 */
    private static function expiryFromDays(int $days): ?\DateTimeImmutable
    {
        return $days > 0 ? new \DateTimeImmutable('+' . $days . ' days') : null;
    }
}
