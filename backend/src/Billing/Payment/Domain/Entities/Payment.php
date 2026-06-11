<?php

declare(strict_types=1);

namespace App\Src\Billing\Payment\Domain\Entities;

use App\Src\Billing\Payment\Domain\Exceptions\InvalidPaymentAmountException;
use App\Src\Billing\Payment\Domain\ValueObjects\PaymentId;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use App\Src\Core\Member\Domain\ValueObjects\MembershipPlanId;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

final class Payment
{
    public function __construct(
        public readonly PaymentId          $id,
        public readonly MemberId           $memberId,
        public readonly MembershipPlanId   $membershipPlanId,
        public readonly UserId             $recordedBy,
        public readonly int                $amountCents,
        public readonly \DateTimeImmutable $paymentDate,
        public readonly string             $billingMonth,
        public readonly ?string            $notes,
        public readonly \DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        PaymentId          $id,
        MemberId           $memberId,
        MembershipPlanId   $membershipPlanId,
        UserId             $recordedBy,
        int                $amountCents,
        \DateTimeImmutable $paymentDate,
        ?string            $notes = null,
    ): self {
        if ($amountCents <= 0) {
            throw new InvalidPaymentAmountException('Payment amount must be greater than zero');
        }

        return new self(
            id:               $id,
            memberId:         $memberId,
            membershipPlanId: $membershipPlanId,
            recordedBy:       $recordedBy,
            amountCents:      $amountCents,
            paymentDate:      $paymentDate,
            billingMonth:     $paymentDate->format('Y-m'),
            notes:            $notes,
            createdAt:        new \DateTimeImmutable(),
        );
    }
}
