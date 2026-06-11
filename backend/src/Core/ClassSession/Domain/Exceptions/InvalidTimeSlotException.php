<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Domain\Exceptions;

final class InvalidTimeSlotException extends \DomainException
{
    public function __construct(string $slot)
    {
        parent::__construct("'{$slot}' is not a valid time slot. Valid slots: 07:45, 12:15, 16:15, 17:30, 18:45, 20:00, 21:15");
    }
}
