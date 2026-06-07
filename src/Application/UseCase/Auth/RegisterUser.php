<?php

declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Exception\AuthException;
use App\Application\Port\TransactionManager;
use App\Application\Service\VerificationMailSender;
use App\Domain\Entity\User;
use App\Domain\Repository\UserRepository;
use App\Domain\Service\PasswordPolicy;
use App\Domain\ValueObject\DisplayName;
use App\Domain\ValueObject\Email;
use DateTimeImmutable;

/**
 * 新規ユーザー登録。入力を検証・正規化し、パスワードをハッシュ化して保存、
 * メール確認トークンを発行して確認メールを送る。メール確認まではログインさせない。
 *
 * 検証は値オブジェクト（Email/DisplayName）とポリシー（PasswordPolicy）に委譲し、
 * メール重複はユニーク制約でも原子的に弾く（チェック後挿入のレース対策）。
 */
final class RegisterUser
{
    public function __construct(
        private readonly TransactionManager $tx,
        private readonly UserRepository $users,
        private readonly VerificationMailSender $verificationMail,
    ) {}

    public function execute(string $emailRaw, string $nameRaw, string $passwordRaw, ?DateTimeImmutable $now = null): User
    {
        $now ??= new DateTimeImmutable();

        // --- 入力検証・正規化（不正なら ValidationException） ---
        $email = Email::fromString($emailRaw);
        $name  = DisplayName::fromString($nameRaw);
        PasswordPolicy::assertValid($passwordRaw);

        if ($this->users->existsByEmail($email->value)) {
            throw AuthException::emailTaken();
        }

        $passwordHash = password_hash($passwordRaw, PASSWORD_DEFAULT);

        $user = $this->tx->run(function () use ($email, $name, $passwordHash, $now): User {
            $user = User::register($email->value, $name->value, $passwordHash, $now);

            try {
                $this->users->insert($user);
            } catch (\PDOException $e) {
                // ユニーク制約違反（23000）＝同時登録のレース。重複として扱う。
                if (($e->errorInfo[0] ?? '') === '23000') {
                    throw AuthException::emailTaken();
                }
                throw $e;
            }

            return $user;
        });

        // トークン発行・確認メール送信はコミット後（送信失敗でユーザー作成を巻き戻さない）。
        $this->verificationMail->send($user, $now);

        return $user;
    }
}
