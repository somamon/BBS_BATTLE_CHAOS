<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Application\Port\Mailer;

/**
 * 開発用メーラー。実送信せず、メール内容をログファイルと error_log に書き出す。
 * 確認リンクは `var/mail.log` か `docker compose logs php` で確認できる。
 * 本番では SMTP 実装に差し替える（Container のバインドを変えるだけ）。
 */
final class LogMailer implements Mailer
{
    public function __construct(private readonly string $logFile) {}

    public function send(string $to, string $subject, string $body): void
    {
        $entry = sprintf(
            "----- MAIL -----\nTo: %s\nSubject: %s\n\n%s\n----------------\n",
            $to,
            $subject,
            $body,
        );

        $dir = \dirname($this->logFile);
        if (is_dir($dir) || @mkdir($dir, 0775, true) || is_dir($dir)) {
            @file_put_contents($this->logFile, $entry, FILE_APPEND | LOCK_EX);
        }

        // 1行に畳んで error_log（docker のログで追える）。
        error_log('[mail] to=' . $to . ' subject=' . $subject . ' body=' . str_replace("\n", ' ', $body));
    }
}
