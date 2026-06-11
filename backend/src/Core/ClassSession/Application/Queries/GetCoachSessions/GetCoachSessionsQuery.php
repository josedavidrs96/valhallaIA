<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Application\Queries\GetCoachSessions;

use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

final readonly class GetCoachSessionsQuery
{
    public function __construct(
        public UserId $coachId,
    ) {}
}
