<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Domain\Repositories;

use App\Src\Core\Booking\Domain\Entities\Booking;
use App\Src\Core\Booking\Domain\Exceptions\BookingNotFoundException;
use App\Src\Core\Booking\Domain\ReadModels\BookingRM;
use App\Src\Core\Booking\Domain\ReadModels\RosterItemRM;
use App\Src\Core\Booking\Domain\ValueObjects\BookingId;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;

interface BookingRepositoryInterface
{
    /** @throws BookingNotFoundException */
    public function getById(BookingId $id): Booking;

    /** @throws BookingNotFoundException */
    public function getByIdRM(BookingId $id): BookingRM;

    public function findByMemberAndSession(MemberId $memberId, ClassSessionId $sessionId): ?Booking;

    public function findByMemberSessionAndDate(
        MemberId $memberId, ClassSessionId $sessionId, \DateTimeImmutable $sessionDate
    ): ?Booking;

    public function countConfirmedBySession(ClassSessionId $sessionId): int;

    public function countConfirmedForMemberInWeek(
        MemberId $memberId, \DateTimeImmutable $weekStart, \DateTimeImmutable $weekEnd
    ): int;

    public function countConfirmedForMemberOnDate(MemberId $memberId, \DateTimeImmutable $date): int;

    public function findActivePlanMaxWeeklyForMember(MemberId $memberId): ?int;

    public function save(Booking $booking): void;

    /**
     * @return BookingRM[]
     */
    public function findByMember(MemberId $memberId): array;

    /**
     * @return RosterItemRM[]
     */
    public function getRoster(ClassSessionId $sessionId): array;
}
