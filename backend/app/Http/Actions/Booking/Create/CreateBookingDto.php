<?php

declare(strict_types=1);

namespace App\Http\Actions\Booking\Create;

final readonly class CreateBookingDto
{
    public function __construct(
        public string $classSessionId,
    ) {}
}
