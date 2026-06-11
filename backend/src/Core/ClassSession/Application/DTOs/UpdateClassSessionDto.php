<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Application\DTOs;

final readonly class UpdateClassSessionDto
{
    public function __construct(
        public string $classTypeId,
        public ?string $coachId,
        public int $maxCapacity,
    ) {}
}
