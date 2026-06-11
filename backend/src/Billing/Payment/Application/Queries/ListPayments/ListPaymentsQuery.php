<?php

declare(strict_types=1);

namespace App\Src\Billing\Payment\Application\Queries\ListPayments;

final readonly class ListPaymentsQuery
{
    public function __construct(
        public ?string $memberId,
        public ?int    $year,
        public ?int    $month,
        public int     $page    = 1,
        public int     $perPage = 20,
    ) {}
}
