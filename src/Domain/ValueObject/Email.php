<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\ValidationException;

/**
 * メールアドレスの値オブジェクト。生成時に形式・長さを検証し、正規化（小文字化・trim）する。
 * 不正な値ではインスタンス化できないため、以降の層は常に正しいメールを扱える。
 */
final class Email
{
    public const MAX_LENGTH = 255;

    private function __construct(public readonly string $value) {}

    public static function fromString(string $raw): self
    {
        $normalized = strtolower(trim($raw));

        if ($normalized === '') {
            throw ValidationException::field('email', 'validation.email.required', 'メールアドレスを入力してください');
        }
        if (mb_strlen($normalized) > self::MAX_LENGTH) {
            throw ValidationException::field('email', 'validation.email.too_long', 'メールアドレスが長すぎます');
        }
        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw ValidationException::field('email', 'validation.email.invalid', 'メールアドレスの形式が正しくありません');
        }

        return new self($normalized);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
