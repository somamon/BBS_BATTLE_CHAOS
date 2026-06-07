<?php

declare(strict_types=1);

namespace App\Application\Exception;

/** 認証・登録に関するアプリ例外。$key は表示用の翻訳キー。 */
final class AuthException extends \RuntimeException
{
    public function __construct(
        public readonly string $key,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function invalidCredentials(): self
    {
        return new self('err.invalid_credentials', 'メールアドレスまたはパスワードが正しくありません');
    }

    public static function emailUnverified(): self
    {
        return new self('err.email_unverified', 'メールアドレスが未確認です。確認メールのリンクから登録を完了してください');
    }

    public static function invalidToken(): self
    {
        return new self('err.invalid_token', '確認リンクが無効か、有効期限が切れています');
    }

    public static function tooManyAttempts(): self
    {
        return new self('err.too_many_attempts', '試行回数が多すぎます。しばらく時間をおいて再度お試しください');
    }
}
