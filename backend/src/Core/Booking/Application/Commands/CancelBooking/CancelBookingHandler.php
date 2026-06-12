<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Application\Commands\CancelBooking;

use App\Src\Core\Booking\Domain\Exceptions\BookingNotOwnedException;
use App\Src\Core\Booking\Domain\Exceptions\CancellationWindowExpiredException;
use App\Src\Core\Booking\Domain\Repositories\BookingRepositoryInterface;
use App\Src\Core\ClassSession\Domain\Repositories\ClassSessionRepositoryInterface;

final class CancelBookingHandler
{
    public function __construct(
        private readonly BookingRepositoryInterface      $bookingRepo,
        private readonly ClassSessionRepositoryInterface $sessionRepo,
    ) {}

    public function handle(CancelBookingCommand $command): void
    {
        $booking = $this->bookingRepo->getById($command->id);

        if ($booking->memberId->value() !== $command->requestingMemberId->value()) {
            throw new BookingNotOwnedException($command->id->value());
        }

        $session = $this->sessionRepo->getById($booking->classSessionId);

        [$h, $m]          = explode(':', $session->timeSlot->value);
        $sessionDatetime  = $booking->sessionDate->setTime((int) $h, (int) $m, 0);
        $now              = new \DateTimeImmutable('now', new \DateTimeZone(config('app.timezone', 'Europe/Madrid')));

        if ($now >= $sessionDatetime) {
            throw new CancellationWindowExpiredException($command->id->value());
        }

        $booking->cancel();
        $this->bookingRepo->save($booking);
    }
}
