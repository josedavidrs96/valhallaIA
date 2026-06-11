<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Domain\ReadModels;

final readonly class BookingRM
{
    public function __construct(
        public string  $id,
        public string  $memberId,
        public string  $classSessionId,
        public string  $status,
        public string  $dayOfWeek,
        public string  $timeSlot,
        public string  $classTypeName,
        public string  $classTypeSlug,
        public ?string $createdAt,
    ) {}
}
