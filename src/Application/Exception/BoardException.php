<?php

declare(strict_types=1);

namespace App\Application\Exception;

/** 掲示板（スレッド・レス）操作に関するアプリ例外。 */
final class BoardException extends \RuntimeException
{
    public static function threadNotFound(): self
    {
        return new self('スレッドが見つかりません');
    }

    public static function threadDead(): self
    {
        return new self('このスレッドは朽ちており書き込めません');
    }
}
