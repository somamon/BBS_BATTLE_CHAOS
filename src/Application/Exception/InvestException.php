<?php

declare(strict_types=1);

namespace App\Application\Exception;

/** 投資が成立しないことを表すアプリ例外。$key は表示用の翻訳キー。 */
final class InvestException extends \RuntimeException
{
    public function __construct(
        public readonly string $key,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function notFound(): self
    {
        return new self('err.invest_not_found', '投稿が見つかりません');
    }

    public static function dead(): self
    {
        return new self('err.invest_dead', 'この投稿は朽ちており投資できません');
    }

    public static function insufficientFunds(): self
    {
        return new self('err.insufficient_funds', '所持金が足りません');
    }

    public static function invalidAmount(): self
    {
        return new self('err.invest_invalid_amount', '投資額が不正です');
    }

    public static function tooSmall(): self
    {
        return new self('err.invest_too_small', '投資額が小さすぎて株を取得できません');
    }
}
