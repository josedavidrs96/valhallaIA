<?php

declare(strict_types=1);

namespace App\Src\Billing\Payment\Domain\ReadModels;

final readonly class OverdueMemberRM
{
    public function __construct(
        public string  $memberId,
        public int     $memberNumber,
        public string  $firstName,
        public string  $lastName,
        public string  $email,
        public ?string $planName,
        public ?string $lastPaymentDate,
    ) {}
}
