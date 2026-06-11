<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Application\Commands\CreateClassSession;

use App\Src\Core\ClassSession\Domain\Enums\DayOfWeek;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassTypeId;
use App\Src\Core\ClassSession\Domain\ValueObjects\TimeSlot;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

final readonly class CreateClassSessionCommand
{
    public function __construct(
        public ClassSessionId $id,
        public ClassTypeId $classTypeId,
        public ?UserId $coachId,
        public DayOfWeek $dayOfWeek,
        public TimeSlot $timeSlot,
        public int $maxCapacity,
    ) {}
}
