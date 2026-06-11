<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Application\Queries\GetClassRoster;

use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;

final readonly class GetClassRosterQuery
{
    public function __construct(
        public ClassSessionId $classSessionId,
    ) {}
}
