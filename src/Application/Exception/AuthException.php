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

    public static function accountSuspended(): self
    {
        return new self('err.account_suspended', 'このアカウントは利用停止中です');
    }

    public static function invalidToken(): self
    {
        return new self('err.invalid_token', '確認リンクが無効か、有効期限が切れています');
    }

    public static function tooManyAttempts(): self
    {
        return new self('err.too_many_attempts', '試行回数が多すぎます。しばらく時間をおいて再度お試しください');
    }

    public static function googleFailed(): self
    {
        return new self('err.google_failed', 'Googleログインに失敗しました。お手数ですが、もう一度お試しください');
    }

    public static function googleEmailUnverified(): self
    {
        return new self('err.google_email_unverified', 'Googleアカウントのメールアドレスが未確認のため、ログインできません');
    }
}
