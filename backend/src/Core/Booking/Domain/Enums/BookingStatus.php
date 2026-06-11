<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Domain\Enums;

enum BookingStatus: string
{
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}
