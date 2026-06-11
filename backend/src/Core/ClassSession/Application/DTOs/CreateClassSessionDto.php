<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Application\DTOs;

final readonly class CreateClassSessionDto
{
    public function __construct(
        public string $classTypeId,
        public ?string $coachId,
        public string $dayOfWeek,
        public string $timeSlot,
        public int $maxCapacity,
    ) {}
}
