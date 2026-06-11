<?php

declare(strict_types=1);

namespace App\Src\Billing\Payment\Domain\Exceptions;

final class PaymentNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Payment not found: $id");
    }
}
