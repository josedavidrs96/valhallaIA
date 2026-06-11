<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Application\Commands\CancelBooking;

use App\Src\Core\Booking\Domain\Exceptions\BookingNotOwnedException;
use App\Src\Core\Booking\Domain\Repositories\BookingRepositoryInterface;

final class CancelBookingHandler
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookingRepo,
    ) {}

    public function handle(CancelBookingCommand $command): void
    {
        $booking = $this->bookingRepo->getById($command->id);

        if ($booking->memberId->value() !== $command->requestingMemberId->value()) {
            throw new BookingNotOwnedException($command->id->value());
        }

        $booking->cancel();
        $this->bookingRepo->save($booking);
    }
}
