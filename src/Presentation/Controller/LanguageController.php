<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Presentation\Http\Request;
use App\Presentation\Http\Response;
use App\Presentation\I18n\Translator;

final class LanguageController
{
    /** GET /lang/{lang} 言語をCookieに保存して元のページへ戻る */
    public function switch(Request $request): Response
    {
        $lang = Translator::normalize((string) $request->param('lang'));

        $https = ($_SERVER['HTTPS'] ?? '') !== '' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        setcookie('lang', $lang, [
            'expires'  => time() + 31536000, // 1年
            'path'     => '/',
            'httponly' => false,             // JSからの参照は不要だが将来用に可視でも可
            'secure'   => $https,
            'samesite' => 'Lax',
        ]);

        // 戻り先は Referer の「パスのみ」を使う（同一オリジン固定でオープンリダイレクト防止）。
        $back = '/';
        $referer = $request->header('Referer');
        if ($referer !== null) {
            $path = parse_url($referer, PHP_URL_PATH);
            if (is_string($path) && str_starts_with($path, '/')) {
                $back = $path;
                $query = parse_url($referer, PHP_URL_QUERY);
                if (is_string($query) && $query !== '') {
                    $back .= '?' . $query;
                }
            }
        }

        return Response::redirect($back);
    }
}
