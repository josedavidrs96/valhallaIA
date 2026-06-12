<?php

declare(strict_types=1);

namespace App\Http\Actions\Booking\Shared;

use App\Src\Core\Booking\Domain\ReadModels\BookingRM;
use Illuminate\Http\JsonResponse;

final class BookingResource
{
    public function __construct(private readonly BookingRM $rm) {}

    public function toResponse(int $status = 200): JsonResponse
    {
        return response()->json([
            'id'               => $this->rm->id,
            'member_id'        => $this->rm->memberId,
            'class_session_id' => $this->rm->classSessionId,
            'session_date'     => $this->rm->sessionDate,
            'status'           => $this->rm->status,
            'session'          => [
                'day_of_week'     => $this->rm->dayOfWeek,
                'time_slot'       => $this->rm->timeSlot,
                'class_type_name' => $this->rm->classTypeName,
                'class_type_slug' => $this->rm->classTypeSlug,
            ],
            'created_at' => $this->rm->createdAt,
        ], $status);
    }
}
