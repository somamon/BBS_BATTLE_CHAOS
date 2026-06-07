<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * 入力検証の失敗を表すドメイン例外。どの項目がなぜ不正かを保持する。
 */
final class ValidationException extends \DomainException
{
    public function __construct(
        public readonly string $field,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function field(string $field, string $message): self
    {
        return new self($field, $message);
    }
}
