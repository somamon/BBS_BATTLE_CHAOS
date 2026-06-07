<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;

/**
 * パスワード再設定トークン。生トークンはメール送信時のみ存在し、DBには SHA-256 ハッシュだけ残す。
 * 生成は {@see issue()} で行い、生トークン（メール用）とエンティティ（保存用）を同時に得る。
 *
 * 確認メール（{@see EmailVerification}）と同じ設計だが、用途が異なるためテーブル/エンティティを分ける。
 * 乗っ取り被害を抑えるため有効期間は確認メールより短くする（1時間）。
 */
final class PasswordReset
{
    /** トークンの有効期間（秒）。乗っ取り対策で短め。 */
    public const TTL_SECONDS = 3600; // 1時間

    public function __construct(
        public readonly string $tokenHash,
        public readonly string $userId,
        public readonly DateTimeImmutable $expiresAt,
        public readonly DateTimeImmutable $createdAt,
    ) {}

    /**
     * 新しい再設定トークンを発行する。
     * @return array{0:self,1:string} [保存用エンティティ, メールに載せる生トークン]
     */
    public static function issue(string $userId, DateTimeImmutable $now): array
    {
        $rawToken = bin2hex(random_bytes(32)); // 256bit
        $entity = new self(
            tokenHash: hash('sha256', $rawToken),
            userId: $userId,
            expiresAt: $now->modify('+' . self::TTL_SECONDS . ' seconds'),
            createdAt: $now,
        );
        return [$entity, $rawToken];
    }

    /** 生トークンから検索用ハッシュを得る。 */
    public static function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $now->getTimestamp() > $this->expiresAt->getTimestamp();
    }
}
