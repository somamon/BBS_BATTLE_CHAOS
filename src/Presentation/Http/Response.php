<?php

declare(strict_types=1);

namespace App\Presentation\Http;

final class Response
{
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

    /** 実際にクライアントへ送る（最後に1回だけ呼ぶ） */
    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        echo $this->body;
    }
}