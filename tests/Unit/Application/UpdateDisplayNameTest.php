<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCase\User\UpdateDisplayName;
use App\Domain\Entity\User;
use App\Domain\Exception\ValidationException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\InMemoryUserRepository;

final class UpdateDisplayNameTest extends TestCase
{
    private InMemoryUserRepository $users;
    private UpdateDisplayName $useCase;
    private string $uid;

    protected function setUp(): void
    {
        $this->users   = new InMemoryUserRepository();
        $this->useCase = new UpdateDisplayName($this->users);
        $user = User::register('a@example.com', 'ユーザー1234', 'hash', new DateTimeImmutable());
        $this->users->insert($user);
        $this->uid = $user->id;
    }

    public function testChangesAndPersistsName(): void
    {
        $this->useCase->execute($this->uid, '名探偵');
        self::assertSame('名探偵', $this->users->findById($this->uid)->name);
    }

    public function testTrimsName(): void
    {
        $this->useCase->execute($this->uid, '  ねこ  ');
        self::assertSame('ねこ', $this->users->findById($this->uid)->name);
    }

    public function testRejectsEmptyName(): void
    {
        $this->expectException(ValidationException::class);
        $this->useCase->execute($this->uid, '   ');
    }

    public function testRejectsTooLongName(): void
    {
        $this->expectException(ValidationException::class);
        $this->useCase->execute($this->uid, str_repeat('あ', 51));
    }

    public function testRejectsControlChars(): void
    {
        $this->expectException(ValidationException::class);
        $this->useCase->execute($this->uid, "改行\n入り");
    }
}
