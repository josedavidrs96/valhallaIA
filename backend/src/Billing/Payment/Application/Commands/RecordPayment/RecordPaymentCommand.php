<?php

declare(strict_types=1);

namespace App\Src\Billing\Payment\Application\Commands\RecordPayment;

use App\Src\Billing\Payment\Domain\ValueObjects\PaymentId;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use App\Src\Core\Member\Domain\ValueObjects\MembershipPlanId;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

final readonly class RecordPaymentCommand
{
    public function __construct(
        public PaymentId          $paymentId,
        public MemberId           $memberId,
        public MembershipPlanId   $membershipPlanId,
        public UserId             $recordedBy,
        public int                $amountCents,
        public \DateTimeImmutable $paymentDate,
        public ?string            $notes,
    ) {}
}
