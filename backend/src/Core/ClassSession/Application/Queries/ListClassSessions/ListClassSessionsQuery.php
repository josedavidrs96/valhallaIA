<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Application\Queries\ListClassSessions;

use App\Src\Core\ClassSession\Domain\Enums\ClassSessionStatus;
use App\Src\Core\ClassSession\Domain\Enums\DayOfWeek;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

final readonly class ListClassSessionsQuery
{
    public function __construct(
        public ?DayOfWeek $dayOfWeek,
        public ?UserId $coachId,
        public ?ClassSessionStatus $status,
    ) {}
}
