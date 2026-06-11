<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Application\Commands\CreateBooking;

use App\Src\Core\Booking\Domain\ValueObjects\BookingId;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;

final readonly class CreateBookingCommand
{
    public function __construct(
        public BookingId       $id,
        public MemberId        $memberId,
        public ClassSessionId  $classSessionId,
    ) {}
}
