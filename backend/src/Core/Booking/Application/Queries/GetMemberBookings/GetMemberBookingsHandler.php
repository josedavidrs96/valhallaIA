<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Application\Queries\GetMemberBookings;

use App\Src\Core\Booking\Domain\Repositories\BookingRepositoryInterface;

final class GetMemberBookingsHandler
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookingRepo,
    ) {}

    public function handle(GetMemberBookingsQuery $query): array
    {
        return $this->bookingRepo->findByMember($query->memberId);
    }
}
