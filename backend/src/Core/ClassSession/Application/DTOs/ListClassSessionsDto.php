<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Application\DTOs;

final readonly class ListClassSessionsDto
{
    public function __construct(
        public ?string $dayOfWeek,
        public ?string $coachId,
        public ?string $status,
    ) {}
}
