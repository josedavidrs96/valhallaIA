<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Booking\Domain;

use App\Src\Core\Booking\Domain\Services\SessionDateResolver;
use App\Src\Core\ClassSession\Domain\Enums\DayOfWeek;
use App\Src\Core\ClassSession\Domain\ValueObjects\TimeSlot;
use PHPUnit\Framework\TestCase;

final class SessionDateResolverTest extends TestCase
{
    private SessionDateResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new SessionDateResolver();
    }

    // Monday 09:00 — slot is Mon 07:45 (already passed) → next Monday
    public function test_same_day_slot_already_passed_returns_next_week(): void
    {
        $now  = new \DateTimeImmutable('2026-06-15 09:00:00'); // Monday
        $date = $this->resolver->resolve(DayOfWeek::Monday, new TimeSlot('07:45'), $now);

        $this->assertSame('2026-06-22', $date->format('Y-m-d'));
    }

    // Monday 07:00 — slot is Mon 07:45 (not yet passed) → today
    public function test_same_day_slot_not_yet_passed_returns_today(): void
    {
        $now  = new \DateTimeImmutable('2026-06-15 07:00:00'); // Monday
        $date = $this->resolver->resolve(DayOfWeek::Monday, new TimeSlot('07:45'), $now);

        $this->assertSame('2026-06-15', $date->format('Y-m-d'));
    }

    // Wednesday — target is Friday (later this week)
    public function test_later_day_this_week_is_returned(): void
    {
        $now  = new \DateTimeImmutable('2026-06-17 10:00:00'); // Wednesday
        $date = $this->resolver->resolve(DayOfWeek::Friday, new TimeSlot('07:45'), $now);

        $this->assertSame('2026-06-19', $date->format('Y-m-d'));
    }

    // Friday — target is Monday (earlier this week, already passed) → next Monday
    public function test_earlier_day_this_week_returns_next_week(): void
    {
        $now  = new \DateTimeImmutable('2026-06-19 10:00:00'); // Friday
        $date = $this->resolver->resolve(DayOfWeek::Monday, new TimeSlot('07:45'), $now);

        $this->assertSame('2026-06-22', $date->format('Y-m-d'));
    }

    // Thursday exactly at slot time (18:45) → already started → next week
    public function test_exact_slot_datetime_counts_as_passed(): void
    {
        $now  = new \DateTimeImmutable('2026-06-18 18:45:00'); // Thursday
        $date = $this->resolver->resolve(DayOfWeek::Thursday, new TimeSlot('18:45'), $now);

        $this->assertSame('2026-06-25', $date->format('Y-m-d'));
    }
}
