<?php

declare(strict_types=1);

namespace App\Application\Port;

/**
 * メール送信ポート（Application 層が依存する抽象）。
 * 開発環境ではファイル/ログに書き出す実装、本番では SMTP 実装に差し替える。
 */
interface Mailer
{
    public function send(string $to, string $subject, string $body): void;
}
