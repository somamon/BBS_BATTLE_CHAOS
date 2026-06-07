<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\ValidationException;
use App\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testNormalizesToLowercaseAndTrims(): void
    {
        self::assertSame('a@example.com', Email::fromString('  A@Example.COM ')->value);
    }

    public function testRejectsEmptyEmail(): void
    {
        $this->expectException(ValidationException::class);
        Email::fromString('   ');
    }

    public function testRejectsMalformedEmail(): void
    {
        $this->expectException(ValidationException::class);
        Email::fromString('not-an-email');
    }

    public function testRejectsTooLongEmail(): void
    {
        $this->expectException(ValidationException::class);
        Email::fromString(str_repeat('a', 250) . '@example.com');
    }

    public function testAcceptsValidEmail(): void
    {
        self::assertSame('user.name+tag@sub.example.co.jp', Email::fromString('user.name+tag@sub.example.co.jp')->value);
    }
}
