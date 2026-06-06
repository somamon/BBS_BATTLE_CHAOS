<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

class ThreadController
{
    /** GET /thread/{id} */
    public function show(Request $request): Response
    {
        $id = $request->param('id');                 // /thread/{id} の id
        return Response::json(['thread_id' => $id]);
    }

    /** GET /thread/create */
    public function createForm(Request $request): Response
    {
        return Response::html('<h1>スレッド作成フォーム</h1>');
    }

    /** GET /thread/{id:\d+}/posts */
    public function posts(Request $request): Response
    {
        $id = $request->param('id');
        return Response::json(['thread_id' => $id, 'posts' => []]);
    }

    /** POST /api/thread */
    public function create(Request $request): Response
    {
        $title = (string) $request->input('title', '');
        return Response::json(['created' => true, 'title' => $title], 201);
    }
}