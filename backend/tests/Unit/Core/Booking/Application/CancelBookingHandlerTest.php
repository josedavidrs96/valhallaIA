<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Booking\Application;

use App\Src\Core\Booking\Application\Commands\CancelBooking\CancelBookingCommand;
use App\Src\Core\Booking\Application\Commands\CancelBooking\CancelBookingHandler;
use App\Src\Core\Booking\Domain\Entities\Booking;
use App\Src\Core\Booking\Domain\Exceptions\CancellationWindowExpiredException;
use App\Src\Core\Booking\Domain\Repositories\BookingRepositoryInterface;
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
use Tests\TestCase;

final class CancelBookingHandlerTest extends TestCase
{
    private BookingRepositoryInterface&MockObject      $bookingRepo;
    private ClassSessionRepositoryInterface&MockObject $sessionRepo;
    private CancelBookingHandler                       $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bookingRepo = $this->createMock(BookingRepositoryInterface::class);
        $this->sessionRepo = $this->createMock(ClassSessionRepositoryInterface::class);

        $this->handler = new CancelBookingHandler($this->bookingRepo, $this->sessionRepo);
    }

    private function makeSession(string $timeSlot, DayOfWeek $day = DayOfWeek::Monday): ClassSession
    {
        return new ClassSession(
            id:          ClassSessionId::random(),
            classTypeId: ClassTypeId::random(),
            coachId:     null,
            dayOfWeek:   $day,
            timeSlot:    new TimeSlot($timeSlot),
            maxCapacity: 20,
            status:      ClassSessionStatus::Active,
            createdAt:   new \DateTimeImmutable(),
        );
    }

    private function makeBooking(MemberId $memberId, ClassSessionId $sessionId, \DateTimeImmutable $sessionDate): Booking
    {
        return Booking::create(BookingId::random(), $memberId, $sessionId, $sessionDate);
    }

    public function test_throws_cancellation_window_expired_when_session_in_the_past(): void
    {
        $memberId  = MemberId::random();
        $sessionId = ClassSessionId::random();
        // Session date is in the past — cutoff already passed
        $booking = $this->makeBooking($memberId, $sessionId, new \DateTimeImmutable('2026-01-05'));
        $session = $this->makeSession('07:45', DayOfWeek::Monday);

        $this->bookingRepo->method('getById')->willReturn($booking);
        $this->sessionRepo->method('getById')->willReturn($session);

        $this->expectException(CancellationWindowExpiredException::class);

        $this->handler->handle(new CancelBookingCommand($booking->id, $memberId));
    }

    public function test_cancellation_succeeds_for_future_session(): void
    {
        $memberId  = MemberId::random();
        $sessionId = ClassSessionId::random();
        // Far future — always ahead of now
        $booking = $this->makeBooking($memberId, $sessionId, new \DateTimeImmutable('2099-12-31'));
        $session = $this->makeSession('20:00', DayOfWeek::Wednesday);

        $this->bookingRepo->method('getById')->willReturn($booking);
        $this->sessionRepo->method('getById')->willReturn($session);
        $this->bookingRepo->expects($this->once())->method('save');

        $this->handler->handle(new CancelBookingCommand($booking->id, $memberId));
    }
}
