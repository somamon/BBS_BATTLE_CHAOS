<?php

declare(strict_types=1);

namespace App\Application\Exception;

/** 掲示板（スレッド・レス）操作に関するアプリ例外。$key は表示用の翻訳キー。 */
final class BoardException extends \RuntimeException
{
    public function __construct(
        public readonly string $key,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function threadNotFound(): self
    {
        return new self('err.thread_not_found', 'スレッドが見つかりません');
    }

    public static function threadDead(): self
    {
        return new self('err.thread_dead', 'このスレッドは朽ちており書き込めません');
    }
}
