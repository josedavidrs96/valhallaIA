<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Booking\Domain;

use App\Src\Core\Booking\Domain\Entities\Booking;
use App\Src\Core\Booking\Domain\Enums\BookingStatus;
use App\Src\Core\Booking\Domain\Exceptions\BookingAlreadyCancelledException;
use App\Src\Core\Booking\Domain\ValueObjects\BookingId;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use PHPUnit\Framework\TestCase;

final class BookingTest extends TestCase
{
    private function makeBooking(): Booking
    {
        return Booking::create(
            BookingId::random(),
            MemberId::random(),
            ClassSessionId::random(),
            new \DateTimeImmutable('2026-06-16'),
        );
    }

    public function test_create_returns_confirmed_booking(): void
    {
        $booking = $this->makeBooking();
        $this->assertSame(BookingStatus::Confirmed, $booking->status());
    }

    public function test_cancel_transitions_to_cancelled(): void
    {
        $booking = $this->makeBooking();
        $booking->cancel();
        $this->assertSame(BookingStatus::Cancelled, $booking->status());
    }

    public function test_cancel_already_cancelled_throws(): void
    {
        $this->expectException(BookingAlreadyCancelledException::class);
        $booking = $this->makeBooking();
        $booking->cancel();
        $booking->cancel();
    }
}
