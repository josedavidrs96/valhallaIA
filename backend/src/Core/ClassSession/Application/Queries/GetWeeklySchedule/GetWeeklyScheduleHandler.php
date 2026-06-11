<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Application\Queries\GetWeeklySchedule;

use App\Src\Core\ClassSession\Domain\Repositories\ClassSessionRepositoryInterface;

final class GetWeeklyScheduleHandler
{
    public function __construct(private readonly ClassSessionRepositoryInterface $sessions) {}

    /** @return \App\Src\Core\ClassSession\Domain\ReadModels\ClassSessionRM[] */
    public function handle(GetWeeklyScheduleQuery $query): array
    {
        return $this->sessions->findWeeklySchedule();
    }
}
