<?php

declare(strict_types=1);

namespace App\Http\Actions\Booking\Shared;

use App\Src\Core\Booking\Domain\ReadModels\BookingRM;
use Illuminate\Http\JsonResponse;

final class BookingListResource
{
    public function __construct(
        private readonly array $items,
        private readonly int   $weeklyUsed = 0,
        private readonly int   $weeklyMax  = 0,
    ) {}

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'data' => array_map(fn(BookingRM $rm) => [
                'id'               => $rm->id,
                'class_session_id' => $rm->classSessionId,
                'session_date'     => $rm->sessionDate,
                'status'           => $rm->status,
                'session'          => [
                    'day_of_week'     => $rm->dayOfWeek,
                    'time_slot'       => $rm->timeSlot,
                    'class_type_name' => $rm->classTypeName,
                    'class_type_slug' => $rm->classTypeSlug,
                ],
                'created_at' => $rm->createdAt,
            ], $this->items),
            'weekly_used' => $this->weeklyUsed,
            'weekly_max'  => $this->weeklyMax,
        ]);
    }
}
