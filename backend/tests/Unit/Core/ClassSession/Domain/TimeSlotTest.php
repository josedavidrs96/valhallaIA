<?php

declare(strict_types=1);

namespace Tests\Unit\Core\ClassSession\Domain;

use App\Src\Core\ClassSession\Domain\Exceptions\InvalidTimeSlotException;
use App\Src\Core\ClassSession\Domain\ValueObjects\TimeSlot;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TimeSlotTest extends TestCase
{
    #[DataProvider('validSlotsProvider')]
    public function test_valid_slot_creates_without_exception(string $slot): void
    {
        $ts = new TimeSlot($slot);

        $this->assertSame($slot, $ts->value);
    }

    public static function validSlotsProvider(): array
    {
        return [
            ['07:45'],
            ['12:15'],
            ['16:15'],
            ['17:30'],
            ['18:45'],
            ['20:00'],
            ['21:15'],
        ];
    }

    public function test_invalid_slot_throws(): void
    {
        $this->expectException(InvalidTimeSlotException::class);

        new TimeSlot('10:00');
    }

    public function test_empty_slot_throws(): void
    {
        $this->expectException(InvalidTimeSlotException::class);

        new TimeSlot('');
    }

    public function test_valid_values_returns_7_slots(): void
    {
        $slots = TimeSlot::validValues();

        $this->assertCount(7, $slots);
        $this->assertContains('07:45', $slots);
        $this->assertContains('21:15', $slots);
    }
}
