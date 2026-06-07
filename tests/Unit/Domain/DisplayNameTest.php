<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\ValidationException;
use App\Domain\ValueObject\DisplayName;
use PHPUnit\Framework\TestCase;

final class DisplayNameTest extends TestCase
{
    public function testTrimsAndKeeps(): void
    {
        self::assertSame('目利き太郎', DisplayName::fromString('  目利き太郎 ')->value);
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(ValidationException::class);
        DisplayName::fromString("  \t ");
    }

    public function testRejectsTooLong(): void
    {
        $this->expectException(ValidationException::class);
        DisplayName::fromString(str_repeat('あ', 51));
    }

    public function testRejectsControlCharacters(): void
    {
        $this->expectException(ValidationException::class);
        DisplayName::fromString("name\nwith-newline");
    }
}
