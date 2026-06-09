<?php

declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Exception\AuthException;
use App\Application\Port\Logger;
use App\Application\Port\TransactionManager;
use App\Domain\Entity\User;
use App\Domain\Repository\UserRepository;
use App\Domain\ValueObject\Email;
use DateTimeImmutable;

/**
 * Google ログイン。検証済みの Google プロフィール（sub / email / name）から
 * ローカルユーザーを特定・連携・新規作成してログイン対象を返す。
 *
 * 連携方針:
 *  1. google_sub 一致のアカウントがあればそれ（=連携済み）。
 *  2. Google がメール未確認なら拒否（なりすまし・誤連携の防止）。
 *  3. 同一メールの既存アカウントがあれば連携（Google が当該メールの保有を証明済み）。
 *  4. どちらも無ければ新規作成（パスワード無し・メール確認済み）。
 */
final class LoginWithGoogle
{
    public function __construct(
        private readonly TransactionManager $tx,
        private readonly UserRepository $users,
        private readonly ?Logger $logger = null,
    ) {}

    public function execute(
        string $googleSub,
        string $emailRaw,
        bool $emailVerified,
        string $nameRaw,
        ?DateTimeImmutable $now = null,
    ): User {
        $now ??= new DateTimeImmutable();

        $googleSub = trim($googleSub);
        if ($googleSub === '') {
            throw AuthException::googleFailed();
        }

        // 1. 連携済みアカウント。
        $linked = $this->users->findByGoogleSub($googleSub);
        if ($linked !== null) {
            if (!$linked->isActive()) {
                throw AuthException::accountSuspended();
            }
            return $linked;
        }

        // 2. Google がメール未確認なら、連携も作成もしない。
        if (!$emailVerified) {
            throw AuthException::googleEmailUnverified();
        }

        try {
            $email = Email::fromString($emailRaw);
        } catch (\Throwable) {
            throw AuthException::googleFailed();
        }
        // Google の表示名は本名であることが多い。公開ランキング等に出る表示名へは使わず、
        // プライバシー優先で匿名ハンドルを既定にする（ユーザーはマイページで任意に変更できる）。
        $name = $this->randomHandle();

        return $this->tx->run(function () use ($googleSub, $email, $name, $now): User {
            // 3. 同一メールの既存アカウントに連携。
            $existing = $this->users->findByEmail($email->value);
            if ($existing !== null) {
                if ($existing->isBot) {
                    throw AuthException::googleFailed(); // NPC は乗っ取らせない
                }
                $existing->linkGoogle($googleSub, $now);
                $this->users->save($existing);
                return $existing;
            }

            // 4. 新規作成。
            $user = User::fromGoogle($email->value, $name, $googleSub, $now);
            try {
                $this->users->insert($user);
            } catch (\PDOException $e) {
                // 同時ログインのレース（email/google_sub のユニーク違反）。既存を引き直す。
                if (($e->errorInfo[0] ?? '') === '23000') {
                    $raced = $this->users->findByGoogleSub($googleSub)
                        ?? $this->users->findByEmail($email->value);
                    if ($raced !== null) {
                        return $raced;
                    }
                }
                throw $e;
            }

            $this->logger?->event('user_registered', ['user_id' => $user->id, 'via' => 'google']);
            return $user;
        });
    }

    /** 新規Googleユーザーの既定表示名。本名を避けた匿名ハンドル（後からマイページで変更可）。 */
    private function randomHandle(): string
    {
        return 'ユーザー' . random_int(1000, 999999);
    }
}
