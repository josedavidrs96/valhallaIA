<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Application\Commands\CreateBooking;

use App\Src\Core\Booking\Domain\Entities\Booking;
use App\Src\Core\Booking\Domain\Exceptions\BookingAlreadyExistsException;
use App\Src\Core\Booking\Domain\Exceptions\DailyLimitReachedException;
use App\Src\Core\Booking\Domain\Exceptions\MemberHasNoPlanException;
use App\Src\Core\Booking\Domain\Exceptions\SessionFullException;
use App\Src\Core\Booking\Domain\Exceptions\SessionNotAvailableException;
use App\Src\Core\Booking\Domain\Exceptions\WeeklyLimitReachedException;
use App\Src\Core\Booking\Domain\Repositories\BookingRepositoryInterface;
use App\Src\Core\Booking\Domain\Services\SessionDateResolver;
use App\Src\Core\ClassSession\Domain\Enums\ClassSessionStatus;
use App\Src\Core\ClassSession\Domain\Repositories\ClassSessionRepositoryInterface;

final class CreateBookingHandler
{
    public function __construct(
        private readonly BookingRepositoryInterface      $bookingRepo,
        private readonly ClassSessionRepositoryInterface $sessionRepo,
        private readonly SessionDateResolver             $sessionDateResolver,
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

        $sessionDate = $this->sessionDateResolver->resolve(
            $session->dayOfWeek,
            $session->timeSlot,
            $command->sessionDate,
        );

        if ($this->bookingRepo->findByMemberSessionAndDate($command->memberId, $command->classSessionId, $sessionDate) !== null) {
            throw new BookingAlreadyExistsException($command->memberId->value());
        }

        if ($this->bookingRepo->countConfirmedForMemberOnDate($command->memberId, $sessionDate) > 0) {
            throw new DailyLimitReachedException($sessionDate->format('Y-m-d'));
        }

        $maxWeekly = $this->bookingRepo->findActivePlanMaxWeeklyForMember($command->memberId);

        if ($maxWeekly === null) {
            throw new MemberHasNoPlanException($command->memberId->value());
        }

        $weekStart = $this->weekStart($sessionDate);
        $weekEnd   = $weekStart->modify('+6 days')->setTime(23, 59, 59);
        $used      = $this->bookingRepo->countConfirmedForMemberInWeek($command->memberId, $weekStart, $weekEnd);

        if ($used >= $maxWeekly) {
            throw new WeeklyLimitReachedException($used, $maxWeekly);
        }

        $booking = Booking::create($command->id, $command->memberId, $command->classSessionId, $sessionDate);
        $this->bookingRepo->save($booking);
    }

    private function weekStart(\DateTimeImmutable $date): \DateTimeImmutable
    {
        $iso  = (int) $date->format('N');
        return $date->modify('-' . ($iso - 1) . ' days')->setTime(0, 0, 0);
    }
}
