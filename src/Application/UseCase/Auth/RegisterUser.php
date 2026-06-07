<?php

declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Port\Mailer;
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
 * メールアドレス列挙対策：アドレスが既存でもエラーにせず、成功時と同じ応答に見せる。
 * 既存の場合は確認メールの代わりに「登録済みのお知らせ」を本人へ送る。
 * 存在の有無で処理時間が変わらないよう、判定前に必ずパスワードをハッシュ化する。
 */
final class RegisterUser
{
    public function __construct(
        private readonly TransactionManager $tx,
        private readonly UserRepository $users,
        private readonly VerificationMailSender $verificationMail,
        private readonly Mailer $mailer,
    ) {}

    public function execute(string $emailRaw, string $nameRaw, string $passwordRaw, ?DateTimeImmutable $now = null): void
    {
        $now ??= new DateTimeImmutable();

        // --- 入力検証・正規化（不正なら ValidationException。アカウント有無は漏らさない） ---
        $email = Email::fromString($emailRaw);
        $name  = DisplayName::fromString($nameRaw);
        PasswordPolicy::assertValid($passwordRaw);

        // 既存・新規で処理量を揃えるため、判定前に必ずハッシュ化する（タイミング列挙対策）。
        $passwordHash = password_hash($passwordRaw, PASSWORD_DEFAULT);

        $user = $this->tx->run(function () use ($email, $name, $passwordHash, $now): ?User {
            if ($this->users->existsByEmail($email->value)) {
                return null; // 既存
            }

            $newUser = User::register($email->value, $name->value, $passwordHash, $now);
            try {
                $this->users->insert($newUser);
            } catch (\PDOException $e) {
                // ユニーク制約違反（23000）＝同時登録のレース。既存として扱う。
                if (($e->errorInfo[0] ?? '') === '23000') {
                    return null;
                }
                throw $e;
            }

            return $newUser;
        });

        // コミット後にメール送信。送信失敗はログに留め UX を止めない（ユーザーは再送で復旧可）。
        try {
            if ($user !== null) {
                $this->verificationMail->send($user, $now);
            } else {
                $this->sendAlreadyRegisteredNotice($email->value);
            }
        } catch (\Throwable $e) {
            error_log('[mail] 登録メール送信失敗: ' . $e->getMessage());
        }
    }

    /** 既に登録済みのアドレスへの新規登録試行を本人へ通知する（攻撃者には新規と区別がつかない）。 */
    private function sendAlreadyRegisteredNotice(string $email): void
    {
        $body = <<<TXT
        このメールアドレスで新規登録が試みられましたが、すでにアカウントが存在します。

        ご本人の操作であれば、ログイン画面からログインしてください。
        パスワードをお忘れの場合や心当たりがない場合は、このメールを破棄して問題ありません。
        TXT;

        $this->mailer->send($email, '【BBS BATTLE CHAOS】登録済みのお知らせ', $body);
    }
}
