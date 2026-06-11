<?php

declare(strict_types=1);

namespace App\Src\Billing\Payment\Domain\ReadModels;

final readonly class PaymentListItemRM
{
    public function __construct(
        public string  $id,
        public int     $memberNumber,
        public string  $memberFirstName,
        public string  $memberLastName,
        public int     $amountCents,
        public string  $paymentDate,
        public string  $billingMonth,
        public string  $planName,
        public ?string $createdAt,
    ) {}
}
