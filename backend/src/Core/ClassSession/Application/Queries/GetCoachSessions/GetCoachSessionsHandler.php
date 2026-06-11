<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Application\Queries\GetCoachSessions;

use App\Src\Core\ClassSession\Domain\Repositories\ClassSessionRepositoryInterface;

final class GetCoachSessionsHandler
{
    public function __construct(private readonly ClassSessionRepositoryInterface $sessions) {}

    /** @return \App\Src\Core\ClassSession\Domain\ReadModels\ClassSessionRM[] */
    public function handle(GetCoachSessionsQuery $query): array
    {
        return $this->sessions->findByCoachRM($query->coachId);
    }
}
