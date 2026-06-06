<?php

declare(strict_types=1);
namespace App\Presentation\Http;

final class Request
{

    private array $params = [];
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query, //GETリクエストのクエリパラメータ
        private readonly array $body,  //POSTリクエストのボディデータ
        private readonly array $server, // $_SERVER
        private readonly array $cookies // $_COOKIE
    ){}
    public static function fromGlobals(): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = rawurldecode(parse_url($uri, PHP_URL_PATH) ?: '/');

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')){
            $body = json_decode(file_get_contents('php://input') ?: '', true) ?? [];
        } else {
            $body = $_POST;
        }
        return new self(
            method: strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            path: $path,
            query: $_GET,
            body: $body,
            server: $_SERVER,
            cookies: $_COOKIE,
        );
    }
    public function method(): string { return $this->method; }
    public function path(): string { return $this->path; }
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }
    public function withParams(array $params): self
    {
        $clone = clone $this;
        $clone->params = $params;
        return $clone;
    }
    public function header(string $name): ?string{
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$key] ?? null;
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function isHtmx(): bool
    {
        return $this->header('HX-Request') !== null;
    }

}