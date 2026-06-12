<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Booking\Application;

use App\Src\Core\Booking\Application\Commands\CreateBooking\CreateBookingCommand;
use App\Src\Core\Booking\Application\Commands\CreateBooking\CreateBookingHandler;
use App\Src\Core\Booking\Domain\Exceptions\DailyLimitReachedException;
use App\Src\Core\Booking\Domain\Exceptions\MemberHasNoPlanException;
use App\Src\Core\Booking\Domain\Exceptions\WeeklyLimitReachedException;
use App\Src\Core\Booking\Domain\Repositories\BookingRepositoryInterface;
use App\Src\Core\Booking\Domain\Services\SessionDateResolver;
use App\Src\Core\Booking\Domain\ValueObjects\BookingId;
use App\Src\Core\ClassSession\Domain\Entities\ClassSession;
use App\Src\Core\ClassSession\Domain\Enums\ClassSessionStatus;
use App\Src\Core\ClassSession\Domain\Enums\DayOfWeek;
use App\Src\Core\ClassSession\Domain\Repositories\ClassSessionRepositoryInterface;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassTypeId;
use App\Src\Core\ClassSession\Domain\ValueObjects\TimeSlot;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateBookingHandlerTest extends TestCase
{
    private BookingRepositoryInterface&MockObject      $bookingRepo;
    private ClassSessionRepositoryInterface&MockObject $sessionRepo;
    private CreateBookingHandler                       $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bookingRepo = $this->createMock(BookingRepositoryInterface::class);
        $this->sessionRepo = $this->createMock(ClassSessionRepositoryInterface::class);

        $this->handler = new CreateBookingHandler(
            $this->bookingRepo,
            $this->sessionRepo,
            new SessionDateResolver(),
        );
    }

    private function makeSession(): ClassSession
    {
        return new ClassSession(
            id:          ClassSessionId::random(),
            classTypeId: ClassTypeId::random(),
            coachId:     null,
            dayOfWeek:   DayOfWeek::Monday,
            timeSlot:    new TimeSlot('20:00'),
            maxCapacity: 20,
            status:      ClassSessionStatus::Active,
            createdAt:   new \DateTimeImmutable(),
        );
    }

    private function makeCommand(\DateTimeImmutable $now): CreateBookingCommand
    {
        return new CreateBookingCommand(
            id:             BookingId::random(),
            memberId:       MemberId::random(),
            classSessionId: ClassSessionId::random(),
            sessionDate:    $now,
        );
    }

    public function test_throws_daily_limit_when_already_booked_same_day(): void
    {
        $session = $this->makeSession();
        $now     = new \DateTimeImmutable('2026-06-15 10:00:00'); // Monday

        $this->sessionRepo->method('getById')->willReturn($session);
        $this->bookingRepo->method('countConfirmedBySession')->willReturn(0);
        $this->bookingRepo->method('findByMemberSessionAndDate')->willReturn(null);
        $this->bookingRepo->method('countConfirmedForMemberOnDate')->willReturn(1); // already 1 booking today

        $this->expectException(DailyLimitReachedException::class);

        $this->handler->handle($this->makeCommand($now));
    }

    public function test_throws_member_has_no_plan_when_no_plan_assigned(): void
    {
        $session = $this->makeSession();
        $now     = new \DateTimeImmutable('2026-06-15 10:00:00'); // Monday, slot not yet passed (20:00)

        $this->sessionRepo->method('getById')->willReturn($session);
        $this->bookingRepo->method('countConfirmedBySession')->willReturn(0);
        $this->bookingRepo->method('findByMemberSessionAndDate')->willReturn(null);
        $this->bookingRepo->method('countConfirmedForMemberOnDate')->willReturn(0);
        $this->bookingRepo->method('findActivePlanMaxWeeklyForMember')->willReturn(null);

        $this->expectException(MemberHasNoPlanException::class);

        $this->handler->handle($this->makeCommand($now));
    }

    public function test_throws_weekly_limit_when_limit_reached(): void
    {
        $session = $this->makeSession();
        $now     = new \DateTimeImmutable('2026-06-15 10:00:00'); // Monday

        $this->sessionRepo->method('getById')->willReturn($session);
        $this->bookingRepo->method('countConfirmedBySession')->willReturn(0);
        $this->bookingRepo->method('findByMemberSessionAndDate')->willReturn(null);
        $this->bookingRepo->method('countConfirmedForMemberOnDate')->willReturn(0);
        $this->bookingRepo->method('findActivePlanMaxWeeklyForMember')->willReturn(2);
        $this->bookingRepo->method('countConfirmedForMemberInWeek')->willReturn(2); // already at limit

        $this->expectException(WeeklyLimitReachedException::class);

        $this->handler->handle($this->makeCommand($now));
    }

    public function test_weekly_limit_not_exceeded_saves_booking(): void
    {
        $session = $this->makeSession();
        $now     = new \DateTimeImmutable('2026-06-15 10:00:00'); // Monday

        $this->sessionRepo->method('getById')->willReturn($session);
        $this->bookingRepo->method('countConfirmedBySession')->willReturn(0);
        $this->bookingRepo->method('findByMemberSessionAndDate')->willReturn(null);
        $this->bookingRepo->method('countConfirmedForMemberOnDate')->willReturn(0);
        $this->bookingRepo->method('findActivePlanMaxWeeklyForMember')->willReturn(3);
        $this->bookingRepo->method('countConfirmedForMemberInWeek')->willReturn(1); // 1 of 3 used

        $this->bookingRepo->expects($this->once())->method('save');

        $this->handler->handle($this->makeCommand($now));
    }
}
