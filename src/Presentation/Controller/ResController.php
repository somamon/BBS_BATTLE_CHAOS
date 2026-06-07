<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Exception\BoardException;
use App\Application\UseCase\Thread\PostReply;
use App\Domain\Exception\ValidationException;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

final class ResController
{
    public function __construct(
        private readonly PostReply $postReply,
        private readonly Auth $auth,
    ) {}

    /** POST /thread/{id}/posts レス投稿 */
    public function create(Request $request): Response
    {
        $threadId   = (string) $request->param('id');
        $content    = (string) $request->input('content', '');
        $authorHash = hash('sha256', $request->ip());
        $authorId   = $this->auth->userId();

        try {
            $this->postReply->execute($threadId, $authorHash, $authorId, $content);
        } catch (BoardException $e) {
            $status = $e->key === 'err.thread_not_found' ? 404 : 403;
            return Response::error($status, t($e->key));
        } catch (ValidationException $e) {
            return Response::error(422, t($e->messageKey));
        }

        return Response::redirect('/thread/' . $threadId);
    }
}
