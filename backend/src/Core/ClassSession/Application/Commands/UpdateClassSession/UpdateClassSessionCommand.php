<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Application\Commands\UpdateClassSession;

use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassTypeId;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

final readonly class UpdateClassSessionCommand
{
    public function __construct(
        public ClassSessionId $id,
        public ClassTypeId $classTypeId,
        public ?UserId $coachId,
        public int $maxCapacity,
    ) {}
}
