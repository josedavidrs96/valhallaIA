<?php

declare(strict_types=1);

namespace App\Http\Actions\ClassSession\Shared;

use App\Src\Core\ClassSession\Domain\ReadModels\ClassSessionRM;
use Illuminate\Http\JsonResponse;

final class ClassSessionResource
{
    public function __construct(private readonly ClassSessionRM $rm) {}

    public function toResponse(int $status = 200): JsonResponse
    {
        return response()->json($this->toArray(), $status);
    }

    public function toArray(): array
    {
        $rm = $this->rm;

        return [
            'id'                 => $rm->id->value(),
            'class_type'         => [
                'id'    => $rm->classTypeId,
                'name'  => $rm->classTypeName,
                'slug'  => $rm->classTypeSlug,
                'color' => $rm->classTypeColor,
            ],
            'coach'              => $rm->coachId ? ['id' => $rm->coachId, 'email' => $rm->coachEmail] : null,
            'day_of_week'        => $rm->dayOfWeek->value,
            'time_slot'          => $rm->timeSlot,
            'max_capacity'       => $rm->maxCapacity,
            'available_capacity' => $rm->availableCapacity,
            'status'             => $rm->status->value,
            'created_at'         => $rm->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
