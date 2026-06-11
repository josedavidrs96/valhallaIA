<?php

declare(strict_types=1);

namespace App\Src\Billing\Payment\Application\Queries\ListPayments;

use App\Src\Billing\Payment\Domain\Repositories\PaymentRepositoryInterface;

final class ListPaymentsHandler
{
    public function __construct(
        private readonly PaymentRepositoryInterface $repo,
    ) {}

    public function handle(ListPaymentsQuery $query): array
    {
        $items = $this->repo->findAll(
            $query->memberId,
            $query->year,
            $query->month,
            $query->page,
            $query->perPage,
        );

        $total = $this->repo->countAll(
            $query->memberId,
            $query->year,
            $query->month,
        );

        return [
            'items'   => $items,
            'total'   => $total,
            'page'    => $query->page,
            'perPage' => $query->perPage,
        ];
    }
}
