<?php

declare(strict_types=1);

namespace App\Http\Actions\Payments\List;

final readonly class ListPaymentsDto
{
    public function __construct(
        public ?string $memberId,
        public ?int    $year,
        public ?int    $month,
        public int     $page,
        public int     $perPage,
    ) {}
}
