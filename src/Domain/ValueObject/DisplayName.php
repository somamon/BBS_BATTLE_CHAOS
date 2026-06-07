<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\ValidationException;

/**
 * 表示名の値オブジェクト。trim・長さ・制御文字を検証する（DBカラムは VARCHAR(50)）。
 */
final class DisplayName
{
    public const MAX_LENGTH = 50;

    private function __construct(public readonly string $value) {}

    public static function fromString(string $raw): self
    {
        $name = trim($raw);

        if ($name === '') {
            throw ValidationException::field('name', 'validation.name.required', '表示名を入力してください');
        }
        if (mb_strlen($name) > self::MAX_LENGTH) {
            throw ValidationException::field('name', 'validation.name.too_long', '表示名は50文字以内にしてください');
        }
        // 改行やタブなどの制御文字を禁止（なりすまし・表示崩れ対策）
        if (preg_match('/[\x00-\x1F\x7F]/u', $name) === 1) {
            throw ValidationException::field('name', 'validation.name.invalid', '表示名に使用できない文字が含まれています');
        }

        return new self($name);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
