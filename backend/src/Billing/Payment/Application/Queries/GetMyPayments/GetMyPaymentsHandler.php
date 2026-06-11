<?php

declare(strict_types=1);

namespace App\Src\Billing\Payment\Application\Queries\GetMyPayments;

use App\Src\Billing\Payment\Domain\Repositories\PaymentRepositoryInterface;

final class GetMyPaymentsHandler
{
    public function __construct(
        private readonly PaymentRepositoryInterface $repo,
    ) {}

    public function handle(GetMyPaymentsQuery $query): array
    {
        return $this->repo->findByMemberId($query->memberId);
    }
}
