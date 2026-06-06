<?php

declare(strict_types=1);

namespace App\Presentation\View;

/**
 * PHPテンプレートをレンダリングする最小ヘルパ。
 * テンプレート内では $data のキーが変数として使え、出力は文字列で返る。
 */
final class View
{
    /**
     * @param string               $template "Thread/index" のようなテンプレート名
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = []): string
    {
        $file = __DIR__ . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("view not found: {$template}");
        }

        extract($data, EXTR_OVERWRITE);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    /** テンプレート内で使う HTML エスケープ用ショートハンド。 */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
