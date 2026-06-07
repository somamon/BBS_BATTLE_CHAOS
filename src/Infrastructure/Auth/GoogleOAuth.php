<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

/**
 * Google アカウントログイン（OAuth 2.0 / OpenID Connect・認可コードフロー）。
 *
 * 方針:
 *  - 機密クライアント（client_secret 保持）＋ state（CSRF）＋ PKCE(S256)。
 *  - id_token の署名検証は行わず、token / userinfo エンドポイントへ TLS で直接アクセスして本人情報を得る
 *    （サーバー間 TLS で取得＝チャネルが認証済みのため、コードフローでは安全な実装）。
 *  - 依存ライブラリを増やさず curl のみで実装する。
 *
 * 設定（環境変数）:
 *  - GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET / GOOGLE_REDIRECT_URL
 */
final class GoogleOAuth
{
    private const AUTH_ENDPOINT     = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_ENDPOINT    = 'https://oauth2.googleapis.com/token';
    private const USERINFO_ENDPOINT = 'https://openidconnect.googleapis.com/v1/userinfo';

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUrl,
    ) {}

    /** 設定が揃っているか（未設定ならログインボタンを出さない・導線を無効化する）。 */
    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '' && $this->redirectUrl !== '';
    }

    /** PKCE の code_verifier を生成する（URLセーフ・43〜128文字）。 */
    public function generateCodeVerifier(): string
    {
        return self::base64Url(random_bytes(32));
    }

    /** state（CSRF対策のワンタイム値）を生成する。 */
    public function generateState(): string
    {
        return bin2hex(random_bytes(16));
    }

    /** 認可リクエストURL。ユーザーをここへリダイレクトする。 */
    public function authUrl(string $state, string $codeVerifier): string
    {
        $params = [
            'client_id'             => $this->clientId,
            'redirect_uri'          => $this->redirectUrl,
            'response_type'         => 'code',
            'scope'                 => 'openid email profile',
            'state'                 => $state,
            'code_challenge'        => self::base64Url(hash('sha256', $codeVerifier, true)),
            'code_challenge_method' => 'S256',
            'access_type'           => 'online',
            'prompt'                => 'select_account',
        ];

        return self::AUTH_ENDPOINT . '?' . http_build_query($params);
    }

    /**
     * 認可コードをトークンに交換し、ユーザー情報を取得する。
     * @return array{sub:string,email:string,emailVerified:bool,name:string}
     * @throws \RuntimeException 交換・取得に失敗した場合
     */
    public function fetchProfile(string $code, string $codeVerifier): array
    {
        $token = $this->postForm(self::TOKEN_ENDPOINT, [
            'code'          => $code,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUrl,
            'grant_type'    => 'authorization_code',
            'code_verifier' => $codeVerifier,
        ]);

        $accessToken = $token['access_token'] ?? null;
        if (!is_string($accessToken) || $accessToken === '') {
            throw new \RuntimeException('Google トークン交換に失敗しました');
        }

        $info = $this->getJson(self::USERINFO_ENDPOINT, $accessToken);

        $sub   = $info['sub']   ?? null;
        $email = $info['email'] ?? null;
        if (!is_string($sub) || $sub === '' || !is_string($email) || $email === '') {
            throw new \RuntimeException('Google ユーザー情報の取得に失敗しました');
        }

        // email_verified は bool または "true"/"false" 文字列で返り得る。
        $verifiedRaw = $info['email_verified'] ?? false;
        $emailVerified = $verifiedRaw === true || $verifiedRaw === 'true' || $verifiedRaw === 1 || $verifiedRaw === '1';

        $name = $info['name'] ?? '';
        if (!is_string($name)) {
            $name = '';
        }

        return [
            'sub'           => $sub,
            'email'         => $email,
            'emailVerified' => $emailVerified,
            'name'          => $name,
        ];
    }

    /** @param array<string,string> $fields @return array<string,mixed> */
    private function postForm(string $url, array $fields): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'],
        ]);
        return $this->execJson($ch);
    }

    /** @return array<string,mixed> */
    private function getJson(string $url, string $bearer): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Accept: application/json', 'Authorization: Bearer ' . $bearer],
        ]);
        return $this->execJson($ch);
    }

    /** @param \CurlHandle $ch @return array<string,mixed> */
    private function execJson($ch): array
    {
        $body   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException('Google への通信に失敗しました: ' . $err);
        }
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException('Google からエラー応答 (HTTP ' . $status . ')');
        }

        $decoded = json_decode((string) $body, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Google 応答の解析に失敗しました');
        }
        return $decoded;
    }

    /** URLセーフ Base64（パディング無し）。 */
    private static function base64Url(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
