<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Presentation\Http\Response;
use App\Presentation\View\View;

/**
 * 管理画面の共通レイアウト描画。公開側の RendersLayout とは分離（相場/me等を持ち込まない）。
 */
trait RendersAdmin
{
    /**
     * @param array<string,mixed> $data
     */
    private function adminPage(string $active, string $title, string $template, array $data = [], int $status = 200): Response
    {
        $content = View::render($template, $data);
        $html = View::render('Admin/layout', [
            'title'   => $title,
            'active'  => $active,
            'content' => $content,
        ]);
        return Response::html($html, $status);
    }
}
