<?php

declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Exception\AuthException;
use App\Application\Port\Logger;
use App\Application\Port\TransactionManager;
use App\Domain\Entity\User;
use App\Domain\Repository\UserRepository;
use App\Domain\ValueObject\DisplayName;
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
        $name = $this->safeDisplayName($nameRaw, $email->value);

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

    /** Google の表示名を安全な DisplayName に整える（不正・長すぎ・空はフォールバック）。 */
    private function safeDisplayName(string $nameRaw, string $email): string
    {
        // 制御文字を除去し、50文字に丸める。
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', trim($nameRaw)) ?? '';
        $name = mb_substr($name, 0, DisplayName::MAX_LENGTH);

        if ($name === '') {
            // メールのローカル部、それも不可なら固定文言。
            $local = (string) strstr($email, '@', true);
            $name  = mb_substr(preg_replace('/[\x00-\x1F\x7F]/u', '', $local) ?? '', 0, DisplayName::MAX_LENGTH);
            if ($name === '') {
                $name = 'ユーザー';
            }
        }

        // 念のため VO で最終検証（ここを通れば保存可能）。失敗時は固定文言。
        try {
            return DisplayName::fromString($name)->value;
        } catch (\Throwable) {
            return 'ユーザー';
        }
    }
}
