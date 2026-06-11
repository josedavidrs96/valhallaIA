<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Application\Commands\CancelBooking;

use App\Src\Core\Booking\Domain\ValueObjects\BookingId;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;

final readonly class CancelBookingCommand
{
    public function __construct(
        public BookingId $id,
        public MemberId  $requestingMemberId,
    ) {}
}
