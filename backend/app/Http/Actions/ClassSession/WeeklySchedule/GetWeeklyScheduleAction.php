<?php

declare(strict_types=1);

namespace App\Http\Actions\ClassSession\WeeklySchedule;

use App\Http\Actions\ClassSession\Shared\WeeklyScheduleResource;
use App\Src\Core\ClassSession\Application\Queries\GetWeeklySchedule\GetWeeklyScheduleHandler;
use App\Src\Core\ClassSession\Application\Queries\GetWeeklySchedule\GetWeeklyScheduleQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetWeeklyScheduleAction
{
    public function __construct(private readonly GetWeeklyScheduleHandler $handler) {}

    public function __invoke(Request $request): JsonResponse
    {
        $sessions = $this->handler->handle(new GetWeeklyScheduleQuery());

        return (new WeeklyScheduleResource($sessions))->toResponse();
    }
}
