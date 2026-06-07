<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Application\Port\Mailer;

final class FakeMailer implements Mailer
{
    /** @var array<int,array{to:string,subject:string,body:string}> */
    public array $sent = [];

    public function send(string $to, string $subject, string $body): void
    {
        $this->sent[] = ['to' => $to, 'subject' => $subject, 'body' => $body];
    }

    public function lastBody(): ?string
    {
        if ($this->sent === []) {
            return null;
        }
        return $this->sent[array_key_last($this->sent)]['body'];
    }

    /** 直近メール本文から確認トークンを取り出す。 */
    public function lastToken(): ?string
    {
        $body = $this->lastBody();
        if ($body !== null && preg_match('/token=([0-9a-f]+)/', $body, $m) === 1) {
            return $m[1];
        }
        return null;
    }
}
