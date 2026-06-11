<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Application\Commands\CreateBooking;

use App\Src\Core\Booking\Domain\Entities\Booking;
use App\Src\Core\Booking\Domain\Exceptions\BookingAlreadyExistsException;
use App\Src\Core\Booking\Domain\Exceptions\SessionFullException;
use App\Src\Core\Booking\Domain\Exceptions\SessionNotAvailableException;
use App\Src\Core\Booking\Domain\Repositories\BookingRepositoryInterface;
use App\Src\Core\ClassSession\Domain\Enums\ClassSessionStatus;
use App\Src\Core\ClassSession\Domain\Repositories\ClassSessionRepositoryInterface;

final class CreateBookingHandler
{
    public function __construct(
        private readonly BookingRepositoryInterface      $bookingRepo,
        private readonly ClassSessionRepositoryInterface $sessionRepo,
    ) {}

    public function handle(CreateBookingCommand $command): void
    {
        $session = $this->sessionRepo->getById($command->classSessionId);

        if ($session->status() !== ClassSessionStatus::Active) {
            throw new SessionNotAvailableException($command->classSessionId->value());
        }

        if ($this->bookingRepo->countConfirmedBySession($command->classSessionId) >= $session->maxCapacity()) {
            throw new SessionFullException($command->classSessionId->value());
        }

        if ($this->bookingRepo->findByMemberAndSession($command->memberId, $command->classSessionId) !== null) {
            throw new BookingAlreadyExistsException($command->memberId->value());
        }

        $booking = Booking::create($command->id, $command->memberId, $command->classSessionId);
        $this->bookingRepo->save($booking);
    }
}
