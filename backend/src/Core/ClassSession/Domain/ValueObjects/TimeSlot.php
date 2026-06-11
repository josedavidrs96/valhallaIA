<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Domain\ValueObjects;

use App\Src\Core\ClassSession\Domain\Exceptions\InvalidTimeSlotException;

final readonly class TimeSlot
{
    private const VALID_SLOTS = ['07:45', '12:15', '16:15', '17:30', '18:45', '20:00', '21:15'];

    public function __construct(public readonly string $value)
    {
        if (!in_array($value, self::VALID_SLOTS, true)) {
            throw new InvalidTimeSlotException($value);
        }
    }

    public static function validValues(): array
    {
        return self::VALID_SLOTS;
    }
}
