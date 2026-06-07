<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;

/**
 * メール確認トークン。生トークンはメール送信時のみ存在し、DBには SHA-256 ハッシュだけ残す。
 * 生成は {@see issue()} で行い、生トークン（メール用）とエンティティ（保存用）を同時に得る。
 */
final class EmailVerification
{
    /** トークンの有効期間（秒）。 */
    public const TTL_SECONDS = 86400; // 24時間

    public function __construct(
        public readonly string $tokenHash,
        public readonly string $userId,
        public readonly DateTimeImmutable $expiresAt,
        public readonly DateTimeImmutable $createdAt,
    ) {}

    /**
     * 新しい確認トークンを発行する。
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
