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

    /**
     * クライアントIP。リバースプロキシ配下では X-Forwarded-For を見るが、
     * 詐称防止のため「REMOTE_ADDR が信頼できるプロキシ(TRUSTED_PROXIES)のときだけ」XFFを採用する。
     * 採用時は XFF を右から走査し、信頼プロキシでない最初のIP（＝実クライアント）を返す。
     */
    public function ip(): string
    {
        $remote = $this->server['REMOTE_ADDR'] ?? '0.0.0.0';

        $trusted = self::trustedProxies();
        if ($trusted === [] || !self::ipMatchesAny($remote, $trusted)) {
            return $remote; // 前段が未設定/信頼外なら REMOTE_ADDR を採用（XFF詐称を弾く）
        }

        $xff = (string) ($this->server['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($xff === '') {
            return $remote;
        }
        $parts = array_map('trim', explode(',', $xff));
        for ($i = count($parts) - 1; $i >= 0; $i--) {
            if ($parts[$i] !== '' && !self::ipMatchesAny($parts[$i], $trusted)) {
                return $parts[$i];
            }
        }
        return $remote;
    }

    /** @return string[] env TRUSTED_PROXIES（カンマ区切りの IP / CIDR）。 */
    private static function trustedProxies(): array
    {
        $v = getenv('TRUSTED_PROXIES');
        if ($v === false || trim($v) === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $v)), static fn (string $s): bool => $s !== ''));
    }

    /** @param string[] $cidrs */
    private static function ipMatchesAny(string $ip, array $cidrs): bool
    {
        foreach ($cidrs as $c) {
            if (self::ipInCidr($ip, $c)) {
                return true;
            }
        }
        return false;
    }

    /** IP が CIDR（または単一IP）に含まれるか。IPv4/IPv6 両対応。 */
    private static function ipInCidr(string $ip, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return $ip === $cidr;
        }
        [$subnet, $bitsRaw] = explode('/', $cidr, 2);
        $bits = (int) $bitsRaw;

        $ipBin  = @inet_pton($ip);
        $subBin = @inet_pton($subnet);
        if ($ipBin === false || $subBin === false || strlen($ipBin) !== strlen($subBin)) {
            return false;
        }
        $bytes = intdiv($bits, 8);
        $rem   = $bits % 8;
        if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subBin, 0, $bytes)) {
            return false;
        }
        if ($rem !== 0) {
            $mask = 0xff << (8 - $rem) & 0xff;
            if ((ord($ipBin[$bytes]) & $mask) !== (ord($subBin[$bytes]) & $mask)) {
                return false;
            }
        }
        return true;
    }

    public function isHtmx(): bool
    {
        return $this->header('HX-Request') !== null;
    }

}