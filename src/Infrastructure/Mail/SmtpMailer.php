<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Application\Port\Mailer;

/**
 * 依存ライブラリ無しの最小 SMTP メーラー。
 * Mailpit（認証/TLSなし・1025）から実プロバイダ（STARTTLS/SSL＋AUTH LOGIN・587/465）まで対応。
 * 件名・差出人名は UTF-8 を MIME エンコード、本文は base64 で安全に送る。
 */
final class SmtpMailer implements Mailer
{
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $username,
        private readonly string $password,
        private readonly string $encryption, // none | tls | ssl
        private readonly string $fromAddress,
        private readonly string $fromName = 'BBS BATTLE CHAOS',
        private readonly int $timeout = 10,
    ) {}

    public function send(string $to, string $subject, string $body): void
    {
        $transport = $this->encryption === 'ssl' ? "ssl://{$this->host}:{$this->port}" : "tcp://{$this->host}:{$this->port}";

        $fp = @stream_socket_client($transport, $errno, $errstr, $this->timeout);
        if ($fp === false) {
            throw new \RuntimeException("SMTP接続失敗: {$errstr} ({$errno})");
        }
        stream_set_timeout($fp, $this->timeout);

        try {
            $this->expect($fp, 220);
            $this->cmd($fp, 'EHLO ' . $this->heloName(), 250);

            if ($this->encryption === 'tls') {
                $this->cmd($fp, 'STARTTLS', 220);
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('STARTTLS のネゴシエーションに失敗');
                }
                $this->cmd($fp, 'EHLO ' . $this->heloName(), 250);
            }

            if ($this->username !== '') {
                $this->cmd($fp, 'AUTH LOGIN', 334);
                $this->cmd($fp, base64_encode($this->username), 334);
                $this->cmd($fp, base64_encode($this->password), 235);
            }

            $this->cmd($fp, 'MAIL FROM:<' . $this->fromAddress . '>', 250);
            $this->cmd($fp, 'RCPT TO:<' . $to . '>', [250, 251]);
            $this->cmd($fp, 'DATA', 354);

            $this->write($fp, $this->buildMessage($to, $subject, $body) . "\r\n.");
            $this->expect($fp, 250);

            $this->cmd($fp, 'QUIT', [221]);
        } finally {
            fclose($fp);
        }
    }

    private function buildMessage(string $to, string $subject, string $body): string
    {
        $from = $this->fromName !== ''
            ? mb_encode_mimeheader($this->fromName, 'UTF-8', 'B') . ' <' . $this->fromAddress . '>'
            : $this->fromAddress;

        $headers = [
            'From: ' . $from,
            'To: ' . $to,
            'Subject: ' . mb_encode_mimeheader($subject, 'UTF-8', 'B'),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];

        // 本文の行頭 "." を保護（SMTP のドットスタッフィングは base8 化で回避済みだが念のため）。
        $encoded = rtrim(chunk_split(base64_encode($body), 76, "\r\n"));

        return implode("\r\n", $headers) . "\r\n\r\n" . $encoded;
    }

    private function heloName(): string
    {
        $name = parse_url($this->fromAddress, PHP_URL_HOST);
        if (is_string($name) && $name !== '') {
            return $name;
        }
        $parts = explode('@', $this->fromAddress);
        return $parts[1] ?? 'localhost';
    }

    /** コマンド送信＋期待ステータス確認。 */
    private function cmd($fp, string $command, int|array $expected): void
    {
        $this->write($fp, $command);
        $this->expect($fp, $expected);
    }

    private function write($fp, string $line): void
    {
        if (fwrite($fp, $line . "\r\n") === false) {
            throw new \RuntimeException('SMTP書き込み失敗');
        }
    }

    /** 応答を読み、期待コードでなければ例外。複数行応答（"250-"）に対応。 */
    private function expect($fp, int|array $expected): void
    {
        $expected = (array) $expected;
        $code = null;
        $lines = [];
        do {
            $line = fgets($fp, 515);
            if ($line === false) {
                throw new \RuntimeException('SMTP応答なし（タイムアウトの可能性）');
            }
            $lines[] = rtrim($line);
            $code = (int) substr($line, 0, 3);
            $more = isset($line[3]) && $line[3] === '-'; // ハイフンは継続行
        } while ($more);

        if (!in_array($code, $expected, true)) {
            throw new \RuntimeException('SMTP想定外応答: ' . implode(' | ', $lines));
        }
    }
}
