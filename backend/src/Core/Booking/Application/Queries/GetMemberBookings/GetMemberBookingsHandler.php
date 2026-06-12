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
        $bookings = $this->bookingRepo->findByMember($query->memberId);

        $now       = new \DateTimeImmutable('now', new \DateTimeZone(config('app.timezone', 'Europe/Madrid')));
        $iso       = (int) $now->format('N');
        $weekStart = $now->modify('-' . ($iso - 1) . ' days')->setTime(0, 0, 0);
        $weekEnd   = $weekStart->modify('+6 days')->setTime(23, 59, 59);

        $weeklyUsed = $this->bookingRepo->countConfirmedForMemberInWeek($query->memberId, $weekStart, $weekEnd);
        $weeklyMax  = $this->bookingRepo->findActivePlanMaxWeeklyForMember($query->memberId) ?? 0;

        return [
            'bookings'    => $bookings,
            'weekly_used' => $weeklyUsed,
            'weekly_max'  => $weeklyMax,
        ];
    }
}
