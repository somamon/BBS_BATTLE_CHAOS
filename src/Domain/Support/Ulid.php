<?php

declare(strict_types=1);

namespace App\Domain\Support;

/**
 * 26文字の ULID を生成する簡易実装（Crockford Base32）。
 * 先頭10文字 = ミリ秒タイムスタンプ、後半16文字 = ランダム。
 */
final class Ulid
{
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public static function generate(): string
    {
        $ms = (int) (microtime(true) * 1000);

        $time = '';
        for ($i = 0; $i < 10; $i++) {
            $time = self::ALPHABET[$ms % 32] . $time;
            $ms = intdiv($ms, 32);
        }

        $rand = '';
        for ($i = 0; $i < 16; $i++) {
            $rand .= self::ALPHABET[random_int(0, 31)];
        }

        return $time . $rand;
    }
}
