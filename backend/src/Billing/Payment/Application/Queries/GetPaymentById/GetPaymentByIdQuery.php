<?php

declare(strict_types=1);

namespace App\Src\Billing\Payment\Application\Queries\GetPaymentById;

use App\Src\Billing\Payment\Domain\ValueObjects\PaymentId;

final readonly class GetPaymentByIdQuery
{
    public function __construct(
        public PaymentId $id,
    ) {}
}
