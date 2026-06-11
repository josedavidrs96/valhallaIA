<?php

declare(strict_types=1);

namespace App\Http\Actions\Payments\Record;

final readonly class RecordPaymentDto
{
    public function __construct(
        public string             $memberId,
        public string             $membershipPlanId,
        public int                $amountCents,
        public \DateTimeImmutable $paymentDate,
        public ?string            $notes,
    ) {}
}
