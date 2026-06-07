<?php

declare(strict_types=1);

use App\Presentation\I18n\Translator;

if (!function_exists('t')) {
    /**
     * 現在のロケールでキーを翻訳する。テンプレートから使う。
     * @param array<string,string|int> $args
     */
    function t(string $key, array $args = []): string
    {
        return Translator::active()->trans($key, $args);
    }
}

if (!function_exists('current_locale')) {
    function current_locale(): string
    {
        return Translator::active()->locale();
    }
}
