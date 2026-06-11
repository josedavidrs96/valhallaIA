<?php

declare(strict_types=1);

namespace App\Http\Actions\ClassSession\Shared;

use App\Src\Core\ClassSession\Domain\ReadModels\ClassSessionRM;
use Illuminate\Http\JsonResponse;

final class WeeklyScheduleResource
{
    /** @param ClassSessionRM[] $sessions */
    public function __construct(private readonly array $sessions) {}

    public function toResponse(): JsonResponse
    {
        $grouped = [
            'monday'    => [],
            'tuesday'   => [],
            'wednesday' => [],
            'thursday'  => [],
            'friday'    => [],
        ];

        foreach ($this->sessions as $rm) {
            $grouped[$rm->dayOfWeek->value][] = (new ClassSessionResource($rm))->toArray();
        }

        return response()->json($grouped);
    }
}
