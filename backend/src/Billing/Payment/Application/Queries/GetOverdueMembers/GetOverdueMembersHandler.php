<?php

declare(strict_types=1);

namespace App\Src\Billing\Payment\Application\Queries\GetOverdueMembers;

use App\Src\Billing\Payment\Domain\Repositories\PaymentRepositoryInterface;

final class GetOverdueMembersHandler
{
    public function __construct(
        private readonly PaymentRepositoryInterface $repo,
    ) {}

    public function handle(GetOverdueMembersQuery $query): array
    {
        $billingMonth = (new \DateTimeImmutable())->format('Y-m');

        return $this->repo->findOverdueMembers($billingMonth);
    }
}
