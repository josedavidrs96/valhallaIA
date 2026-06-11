<?php

declare(strict_types=1);

namespace App\Src\Billing\Payment\Domain\ReadModels;

final readonly class PaymentDetailRM
{
    public function __construct(
        public string  $id,
        public string  $memberId,
        public int     $memberNumber,
        public string  $memberFirstName,
        public string  $memberLastName,
        public string  $membershipPlanId,
        public string  $planName,
        public string  $recordedBy,
        public int     $amountCents,
        public string  $paymentDate,
        public string  $billingMonth,
        public ?string $notes,
        public ?string $createdAt,
    ) {}
}
