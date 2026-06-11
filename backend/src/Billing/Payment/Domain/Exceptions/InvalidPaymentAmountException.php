<?php

declare(strict_types=1);

namespace App\Src\Billing\Payment\Domain\Exceptions;

final class InvalidPaymentAmountException extends \RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
