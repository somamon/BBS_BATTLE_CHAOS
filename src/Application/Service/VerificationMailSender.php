<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Port\Mailer;
use App\Domain\Entity\EmailVerification;
use App\Domain\Entity\User;
use App\Domain\Repository\EmailVerificationRepository;
use DateTimeImmutable;

/**
 * メール確認トークンの発行・保存・確認メール送信をまとめて行う共有サービス。
 * 新規登録（RegisterUser）と再送（ResendVerification）の両方から使う。
 * 発行時は当該ユーザーの既存トークンをすべて破棄し、常に最新の1本だけを有効にする。
 */
final class VerificationMailSender
{
    public function __construct(
        private readonly EmailVerificationRepository $verifications,
        private readonly Mailer $mailer,
        private readonly string $appUrl,
    ) {}

    public function send(User $user, DateTimeImmutable $now): void
    {
        [$verification, $rawToken] = EmailVerification::issue($user->id, $now);
        $this->verifications->deleteForUser($user->id); // 旧トークンを無効化
        $this->verifications->insert($verification);

        $link = rtrim($this->appUrl, '/') . '/verify?token=' . $rawToken;
        $body = <<<TXT
        BBS BATTLE CHAOS のメールアドレス確認です。

        以下のリンクを開いて、メールアドレスの確認を完了してください（24時間有効）:

        {$link}

        心当たりがない場合は、このメールは破棄してください。
        TXT;

        $this->mailer->send($user->email, '【BBS BATTLE CHAOS】メールアドレスの確認', $body);
    }
}
