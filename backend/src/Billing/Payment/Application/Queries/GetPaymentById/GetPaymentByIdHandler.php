<?php

declare(strict_types=1);

namespace App\Src\Billing\Payment\Application\Queries\GetPaymentById;

use App\Src\Billing\Payment\Domain\ReadModels\PaymentDetailRM;
use App\Src\Billing\Payment\Domain\Repositories\PaymentRepositoryInterface;

final class GetPaymentByIdHandler
{
    public function __construct(
        private readonly PaymentRepositoryInterface $repo,
    ) {}

    public function handle(GetPaymentByIdQuery $query): PaymentDetailRM
    {
        return $this->repo->getDetailById($query->id);
    }
}
