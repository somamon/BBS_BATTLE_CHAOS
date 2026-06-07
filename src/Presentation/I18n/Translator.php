<?php

declare(strict_types=1);

namespace App\Presentation\I18n;

/**
 * 最小の翻訳器。ロケール別のメッセージ辞書を読み、キー→文言に変換する。
 * プレースホルダは {name} 形式で $args から差し込む。未知キーはキーをそのまま返す。
 */
final class Translator
{
    public const SUPPORTED = ['ja', 'en'];
    public const DEFAULT   = 'ja';

    private static ?Translator $active = null;

    /** @param array<string,string> $messages */
    public function __construct(
        private readonly string $locale,
        private readonly array $messages,
    ) {}

    public static function for(string $locale): self
    {
        $locale = self::normalize($locale);
        /** @var array<string,string> $messages */
        $messages = require __DIR__ . "/lang/{$locale}.php";
        return new self($locale, $messages);
    }

    public static function normalize(string $locale): string
    {
        return in_array($locale, self::SUPPORTED, true) ? $locale : self::DEFAULT;
    }

    public static function activate(self $t): void
    {
        self::$active = $t;
    }

    public static function active(): self
    {
        return self::$active ??= self::for(self::DEFAULT);
    }

    public function locale(): string
    {
        return $this->locale;
    }

    /** @param array<string,string|int> $args */
    public function trans(string $key, array $args = []): string
    {
        $msg = $this->messages[$key] ?? $key;
        if ($args !== []) {
            $replace = [];
            foreach ($args as $k => $v) {
                $replace['{' . $k . '}'] = (string) $v;
            }
            $msg = strtr($msg, $replace);
        }
        return $msg;
    }
}
