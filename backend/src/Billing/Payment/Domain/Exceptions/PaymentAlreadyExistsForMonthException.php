<?php

declare(strict_types=1);

namespace App\Src\Billing\Payment\Domain\Exceptions;

final class PaymentAlreadyExistsForMonthException extends \RuntimeException
{
    public function __construct(string $billingMonth)
    {
        parent::__construct("Payment already exists for billing month: $billingMonth");
    }
}
