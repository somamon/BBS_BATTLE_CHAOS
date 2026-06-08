<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Application\Port\AuditLogger;
use App\Domain\Repository\ContactMessageRepository;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Flash;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

/**
 * お問い合わせの管理（一覧・対応済み化）。
 */
final class ContactController
{
    use RendersAdmin;

    public function __construct(
        private readonly ContactMessageRepository $messages,
        private readonly AuditLogger $audit,
        private readonly Auth $auth,
    ) {}

    /** GET /admin/contact */
    public function index(Request $request): Response
    {
        $rows = [];
        foreach ($this->messages->recent(100) as $m) {
            $rows[] = [
                'id'        => $m->id,
                'name'      => $m->name,
                'email'     => $m->email,
                'excerpt'   => mb_substr($m->message, 0, 80),
                'status'    => $m->status,
                'createdAt' => $m->createdAt->format('Y-m-d H:i'),
            ];
        }
        return $this->adminPage('contact', 'お問い合わせ', 'Admin/contact', [
            'messages' => $rows,
            'flash'    => Flash::pull(),
        ]);
    }

    /** POST /admin/contact/{id}/done */
    public function done(Request $request): Response
    {
        $id = (string) $request->param('id');
        $this->messages->setStatus($id, 'done');
        $this->audit->record((string) $this->auth->userId(), 'contact.done', 'contact', $id, null, $request->ip());
        Flash::set('対応済みにしました。');
        return Response::redirect('/admin/contact');
    }
}
