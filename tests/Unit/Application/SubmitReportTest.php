<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCase\Report\SubmitReport;
use App\Domain\Exception\ValidationException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\InMemoryReportRepository;

final class SubmitReportTest extends TestCase
{
    private InMemoryReportRepository $reports;
    private SubmitReport $useCase;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now     = new DateTimeImmutable('2026-01-01 00:00:00');
        $this->reports = new InMemoryReportRepository();
        $this->useCase = new SubmitReport($this->reports);
    }

    public function testCreatesOpenReport(): void
    {
        $this->useCase->execute('post', 'p1', 'abuse', '誹謗中傷です', 'u1', 'iphash', $this->now);

        self::assertCount(1, $this->reports->listOpen());
        self::assertSame(1, $this->reports->countOpen());
    }

    public function testRejectsInvalidReason(): void
    {
        $this->expectException(ValidationException::class);
        $this->useCase->execute('post', 'p1', 'nonsense', null, null, 'iphash', $this->now);
    }

    public function testRejectsInvalidTargetType(): void
    {
        $this->expectException(ValidationException::class);
        $this->useCase->execute('user', 'u1', 'spam', null, null, 'iphash', $this->now);
    }
}
