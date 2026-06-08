<?php

declare(strict_types=1);

namespace App\Application\UseCase\Contact;

use App\Application\Port\Logger;
use App\Application\Port\Mailer;
use App\Domain\Entity\ContactMessage;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\ContactMessageRepository;
use App\Domain\ValueObject\Email;
use DateTimeImmutable;

/**
 * お問い合わせの送信。入力を検証し、運営の連絡先メールへ本文を送る。
 * 返信できるよう、本文に送信者のメールアドレスを明記する（Mailer はヘッダ指定を持たないため）。
 */
final class SubmitContact
{
    private const MESSAGE_MAX = 2000;
    private const NAME_MAX    = 50;

    public function __construct(
        private readonly Mailer $mailer,
        private readonly string $contactTo,
        private readonly ?Logger $logger = null,
        private readonly ?ContactMessageRepository $messages = null,
    ) {}

    /**
     * @param array{ip?:string,locale?:string,userId?:?string} $meta 監査用の付帯情報
     */
    public function execute(string $nameRaw, string $emailRaw, string $messageRaw, array $meta = [], ?DateTimeImmutable $now = null): void
    {
        $now ??= new DateTimeImmutable();

        $name    = trim($nameRaw);
        $message = trim($messageRaw);

        // メールは必須・形式検証（Email VO が ValidationException を投げる）。
        $email = Email::fromString($emailRaw);

        if ($message === '') {
            throw ValidationException::field('message', 'validation.message.required', 'お問い合わせ内容を入力してください');
        }
        if (mb_strlen($message) > self::MESSAGE_MAX) {
            throw ValidationException::field('message', 'validation.message.too_long', 'お問い合わせ内容が長すぎます');
        }
        if (mb_strlen($name) > self::NAME_MAX) {
            throw ValidationException::field('name', 'validation.name.too_long', '表示名は50文字以内にしてください');
        }

        $displayName = $name !== '' ? $name : '(未記入)';
        $replyTo     = $email->value;
        $ip          = $meta['ip']     ?? '-';
        $locale      = $meta['locale'] ?? '-';
        $userIdLine  = $meta['userId'] ?? '(未ログイン)';
        $sentAt      = $now->format('Y-m-d H:i:s');

        $body = <<<TXT
        お問い合わせを受け付けました。

        ---- 内容 ----
        {$message}
        --------------

        差出人: {$displayName}
        返信先: {$replyTo}
        ログインユーザーID: {$userIdLine}
        受信日時: {$sentAt}
        ロケール: {$locale}
        IP: {$ip}
        TXT;

        $userId = $meta['userId'] ?? null;

        // メール送信に加えてDBにも控えを残す（管理画面で一覧・対応するため）。
        $this->messages?->insert(ContactMessage::create(
            $name !== '' ? $name : null,
            $email->value,
            $message,
            $userId,
            $meta['ip'] ?? null,
            $now,
        ));

        $this->mailer->send($this->contactTo, '【BBS BATTLE CHAOS】お問い合わせ', $body);

        $this->logger?->event('contact_submitted', [
            'has_user' => $userId !== null,
            'locale'   => $locale,
        ]);
    }
}
