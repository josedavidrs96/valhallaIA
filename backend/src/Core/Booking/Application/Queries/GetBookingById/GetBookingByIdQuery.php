<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Application\Queries\GetBookingById;

use App\Src\Core\Booking\Domain\ValueObjects\BookingId;

final readonly class GetBookingByIdQuery
{
    public function __construct(
        public BookingId $id,
    ) {}
}
