<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Service\MarketPhaseService;
use App\Domain\Repository\UserRepository;
use App\Presentation\Http\Auth;
use App\Presentation\View\View;
use DateTimeImmutable;

/**
 * 共通レイアウトのレンダリングヘルパ。
 * 相場フェーズ（header表示用）と現在ユーザー情報（$me）を解決して layout に渡す。
 */
trait RendersLayout
{
    /**
     * @param array<string,mixed> $data content テンプレートに渡すデータ
     */
    private function page(
        MarketPhaseService $market,
        Auth $auth,
        UserRepository $users,
        string $title,
        string $template,
        array $data = [],
    ): string {
        $phase = $market->resolve(new DateTimeImmutable())->phase();

        $me = null;
        $uid = $auth->userId();
        if ($uid !== null) {
            $user = $users->findById($uid);
            if ($user !== null) {
                $me = ['name' => $user->name, 'money' => $user->money()];
            }
        }

        $content = View::render($template, $data);

        return View::render('layout', [
            'title'       => $title,
            'phase'       => $phase,
            'me'          => $me,
            'content'     => $content,
            // SEO: ページが $data に description / ogImage を入れれば layout が拾う（無ければ既定）。
            'description' => $data['description'] ?? null,
            'ogImage'     => $data['ogImage'] ?? null,
        ]);
    }
}
