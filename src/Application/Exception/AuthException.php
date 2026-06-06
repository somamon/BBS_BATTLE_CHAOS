<?php

declare(strict_types=1);

namespace App\Application\Exception;

/** 認証・登録に関するアプリ例外（メール重複・資格情報不正）。 */
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
}
