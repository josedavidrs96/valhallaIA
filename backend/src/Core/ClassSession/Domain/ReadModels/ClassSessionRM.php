<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Domain\ReadModels;

use App\Src\Core\ClassSession\Domain\Enums\ClassSessionStatus;
use App\Src\Core\ClassSession\Domain\Enums\DayOfWeek;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;

final readonly class ClassSessionRM
{
    public function __construct(
        public ClassSessionId $id,
        public string $classTypeId,
        public string $classTypeName,
        public string $classTypeSlug,
        public ?string $classTypeColor,
        public ?string $coachId,
        public ?string $coachEmail,
        public DayOfWeek $dayOfWeek,
        public string $timeSlot,
        public int $maxCapacity,
        public ClassSessionStatus $status,
        public \DateTimeImmutable $createdAt,
    ) {}
}
