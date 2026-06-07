<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * 入力検証の失敗を表すドメイン例外。
 * $field=対象項目、$messageKey=表示用の翻訳キー（例 validation.email.invalid）。
 */
final class ValidationException extends \DomainException
{
    public function __construct(
        public readonly string $field,
        public readonly string $messageKey,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function field(string $field, string $messageKey, string $message): self
    {
        return new self($field, $messageKey, $message);
    }
}
