<?php

declare(strict_types=1);

namespace App\Http\Actions\Booking\Shared;

use App\Src\Core\Booking\Domain\ReadModels\RosterItemRM;
use Illuminate\Http\JsonResponse;

final class RosterResource
{
    public function __construct(
        private readonly array $items,
        private readonly int   $confirmedCount,
        private readonly int   $maxCapacity,
    ) {}

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'capacity' => [
                'confirmed' => $this->confirmedCount,
                'available' => max(0, $this->maxCapacity - $this->confirmedCount),
                'max'       => $this->maxCapacity,
            ],
            'roster' => array_map(fn(RosterItemRM $item) => [
                'booking_id'    => $item->bookingId,
                'member_id'     => $item->memberId,
                'member_number' => $item->memberNumber,
                'first_name'    => $item->firstName,
                'last_name'     => $item->lastName,
                'status'        => $item->status,
                'booked_at'     => $item->bookedAt,
            ], $this->items),
        ]);
    }
}
