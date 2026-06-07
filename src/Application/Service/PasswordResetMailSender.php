<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Port\Mailer;
use App\Domain\Entity\PasswordReset;
use App\Domain\Entity\User;
use App\Domain\Repository\PasswordResetRepository;
use DateTimeImmutable;

/**
 * パスワード再設定トークンの発行・保存・案内メール送信をまとめて行う共有サービス。
 * 発行時は当該ユーザーの既存トークンをすべて破棄し、常に最新の1本だけを有効にする。
 * 確認メール（{@see VerificationMailSender}）と同じ方針。
 */
final class PasswordResetMailSender
{
    public function __construct(
        private readonly PasswordResetRepository $resets,
        private readonly Mailer $mailer,
        private readonly string $appUrl,
    ) {}

    public function send(User $user, DateTimeImmutable $now): void
    {
        [$reset, $rawToken] = PasswordReset::issue($user->id, $now);
        $this->resets->deleteForUser($user->id); // 旧トークンを無効化
        $this->resets->insert($reset);

        $link = rtrim($this->appUrl, '/') . '/password/reset?token=' . $rawToken;
        $body = <<<TXT
        BBS BATTLE CHAOS のパスワード再設定のご案内です。

        以下のリンクを開いて、新しいパスワードを設定してください（1時間有効）:

        {$link}

        心当たりがない場合は、このメールを破棄してください。パスワードは変更されません。
        TXT;

        $this->mailer->send($user->email, '【BBS BATTLE CHAOS】パスワード再設定のご案内', $body);
    }
}
