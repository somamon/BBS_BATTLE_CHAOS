<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Application\UseCase\Admin\BanIp;
use App\Domain\Repository\BanRepository;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Flash;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

/**
 * BAN管理（有効なBANの一覧・解除）。BAN追加はコンテンツ画面の「IP BAN」から行う。
 */
final class BanController
{
    use RendersAdmin;

    public function __construct(
        private readonly BanRepository $bans,
        private readonly BanIp $banIp,
        private readonly Auth $auth,
    ) {}

    /** GET /admin/bans */
    public function index(Request $request): Response
    {
        $rows = [];
        foreach ($this->bans->listActive(200) as $b) {
            $rows[] = [
                'id'        => $b->id,
                'kind'      => $b->kind,
                'value'     => mb_substr($b->value, 0, 16) . '…',
                'reason'    => $b->reason,
                'expiresAt' => $b->expiresAt?->format('Y-m-d H:i') ?? '無期限',
                'createdAt' => $b->createdAt->format('Y-m-d H:i'),
            ];
        }
        return $this->adminPage('bans', 'BAN', 'Admin/bans', [
            'bans'  => $rows,
            'flash' => Flash::pull(),
        ]);
    }

    /** POST /admin/bans IPアドレスを直接指定してBAN（days=0で無期限） */
    public function add(Request $request): Response
    {
        $ip     = (string) $request->input('ip', '');
        $reason = trim((string) $request->input('reason', ''));
        $days   = (int) $request->input('days', 0);
        $expiresAt = $days > 0 ? new \DateTimeImmutable('+' . $days . ' days') : null;

        $ok = $this->banIp->banAddress((string) $this->auth->userId(), $ip, $reason !== '' ? $reason : null, $request->ip(), $expiresAt);
        Flash::set($ok ? 'IPをBANしました。' : 'IPアドレスを入力してください。');
        return Response::redirect('/admin/bans');
    }

    /** POST /admin/bans/{id}/remove */
    public function remove(Request $request): Response
    {
        $this->banIp->remove((string) $this->auth->userId(), (int) $request->param('id'), $request->ip());
        Flash::set('BANを解除しました。');
        return Response::redirect('/admin/bans');
    }
}
