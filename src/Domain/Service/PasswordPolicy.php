<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Exception\ValidationException;

/**
 * パスワード強度ポリシー。ハッシュ化前の平文を検証する。
 * 下限8文字。bcrypt は72バイトを超える分を黙って切り捨てるため上限を72バイトに制限する。
 */
final class PasswordPolicy
{
    public const MIN_LENGTH = 8;
    public const MAX_BYTES  = 72;

    public static function assertValid(string $plain): void
    {
        if (str_contains($plain, "\0")) {
            throw ValidationException::field('password', 'パスワードに使用できない文字が含まれています');
        }
        if (mb_strlen($plain) < self::MIN_LENGTH) {
            throw ValidationException::field('password', 'パスワードは8文字以上にしてください');
        }
        if (strlen($plain) > self::MAX_BYTES) {
            throw ValidationException::field('password', 'パスワードが長すぎます（72バイト以内）');
        }
    }
}
