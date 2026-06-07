<?php

declare(strict_types=1);

namespace App\Application\Exception;

/** 投資が成立しないことを表すアプリ例外（残高不足・dead・不正額など）。 */
final class InvestException extends \RuntimeException
{
    public static function notFound(): self
    {
        return new self('投稿が見つかりません');
    }

    public static function dead(): self
    {
        return new self('この投稿は朽ちており投資できません');
    }

    public static function insufficientFunds(): self
    {
        return new self('所持金が足りません');
    }

    public static function invalidAmount(): self
    {
        return new self('投資額が不正です');
    }

    public static function tooSmall(): self
    {
        return new self('投資額が小さすぎて株を取得できません');
    }
}
