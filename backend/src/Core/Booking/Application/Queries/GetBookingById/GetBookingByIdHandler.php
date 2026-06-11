<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Application\Queries\GetBookingById;

use App\Src\Core\Booking\Domain\ReadModels\BookingRM;
use App\Src\Core\Booking\Domain\Repositories\BookingRepositoryInterface;

final class GetBookingByIdHandler
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookingRepo,
    ) {}

    public function handle(GetBookingByIdQuery $query): BookingRM
    {
        return $this->bookingRepo->getByIdRM($query->id);
    }
}
