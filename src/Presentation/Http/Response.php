<?php

declare(strict_types=1);

namespace App\Presentation\Http;

final class Response
{
    /**
     * 全レスポンスに付与するセキュリティヘッダ（明示設定があれば上書きされる）。
     * - frame-ancestors / X-Frame-Options: クリックジャッキング防止
     * - nosniff: MIME スニッフィング防止
     * - Referrer-Policy: URL（確認トークン等）の Referer 漏洩防止
     * - CSP: 既定は self のみ。インラインstyleを使うため style だけ unsafe-inline を許可
     */
    private const SECURITY_HEADERS = [
        'X-Frame-Options'        => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy'        => 'no-referrer',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; base-uri 'none'; form-action 'self'; frame-ancestors 'none'",
    ];

    /** @var array<string,string> */
    private array $headers = [];

    public function __construct(
        private string $body = '',
        private int $status = 200,
    ) {}

    public static function html(string $body, int $status = 200): self
    {
        $res = new self($body, $status);
        $res->headers['Content-Type'] = 'text/html; charset=UTF-8';
        return $res;
    }

    public static function json(array $data, int $status = 200): self
    {
        $res = new self(json_encode($data, JSON_UNESCAPED_UNICODE), $status);
        $res->headers['Content-Type'] = 'application/json; charset=UTF-8';
        return $res;
    }

    public static function redirect(string $url, int $status = 302): self
    {
        $res = new self('', $status);
        $res->headers['Location'] = $url;
        return $res;
    }

    public static function error(int $status, string $message): self
    {
        // メッセージにユーザー入力が混ざっても安全なよう必ずエスケープする
        $safe = htmlspecialchars($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return self::html("<h1>{$status}</h1><p>{$safe}</p>", $status);
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * 送出する全ヘッダ（セキュリティ既定 ＋ 明示設定）。明示設定が既定を上書きする。
     * @return array<string,string>
     */
    public function headers(): array
    {
        return array_merge(self::SECURITY_HEADERS, $this->headers);
    }

    public function status(): int
    {
        return $this->status;
    }

    /** 実際にクライアントへ送る（最後に1回だけ呼ぶ） */
    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers() as $name => $value) {
            header("{$name}: {$value}");
        }
        echo $this->body;
    }
}