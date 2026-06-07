<?php

declare(strict_types=1);

namespace App\Application\Exception;

/** 認証・登録に関するアプリ例外。 */
final class AuthException extends \RuntimeException
{
    public static function emailTaken(): self
    {
        return new self('このメールアドレスは既に登録されています');
    }

    public static function invalidCredentials(): self
    {
        return new self('メールアドレスまたはパスワードが正しくありません');
    }

    public static function emailUnverified(): self
    {
        return new self('メールアドレスが未確認です。確認メールのリンクから登録を完了してください');
    }

    public static function invalidToken(): self
    {
        return new self('確認リンクが無効か、有効期限が切れています');
    }

    public static function tooManyAttempts(): self
    {
        return new self('試行回数が多すぎます。しばらく時間をおいて再度お試しください');
    }
}
