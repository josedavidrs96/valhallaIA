<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Domain\ReadModels;

final readonly class RosterItemRM
{
    public function __construct(
        public string  $bookingId,
        public string  $memberId,
        public int     $memberNumber,
        public string  $firstName,
        public string  $lastName,
        public string  $status,
        public ?string $bookedAt,
    ) {}
}
