<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\ValidationException;
use App\Domain\Service\PasswordPolicy;
use PHPUnit\Framework\TestCase;

final class PasswordPolicyTest extends TestCase
{
    public function testAcceptsEightOrMore(): void
    {
        PasswordPolicy::assertValid('password1');
        $this->expectNotToPerformAssertions();
    }

    public function testRejectsTooShort(): void
    {
        $this->expectException(ValidationException::class);
        PasswordPolicy::assertValid('short7!');  // 7文字
    }

    public function testRejectsOver72Bytes(): void
    {
        $this->expectException(ValidationException::class);
        PasswordPolicy::assertValid(str_repeat('a', 73));
    }

    public function testRejectsNullByte(): void
    {
        $this->expectException(ValidationException::class);
        PasswordPolicy::assertValid("abc\0defgh");
    }
}
