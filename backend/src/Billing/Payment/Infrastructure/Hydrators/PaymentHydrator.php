<?php

declare(strict_types=1);

namespace App\Src\Billing\Payment\Infrastructure\Hydrators;

use App\Src\Billing\Payment\Domain\Entities\Payment;
use App\Src\Billing\Payment\Domain\ValueObjects\PaymentId;
use App\Src\Billing\Payment\Infrastructure\Persistence\PaymentModel;
use App\Src\Billing\Payment\Infrastructure\Tables\PaymentTable;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use App\Src\Core\Member\Domain\ValueObjects\MembershipPlanId;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

final class PaymentHydrator
{
    public function hydrate(PaymentModel $model): Payment
    {
        return new Payment(
            id:               PaymentId::fromString((string) $model->{PaymentTable::ID}),
            memberId:         MemberId::fromString((string) $model->{PaymentTable::MEMBER_ID}),
            membershipPlanId: MembershipPlanId::fromString((string) $model->{PaymentTable::MEMBERSHIP_PLAN_ID}),
            recordedBy:       UserId::fromString((string) $model->{PaymentTable::RECORDED_BY}),
            amountCents:      (int) $model->{PaymentTable::AMOUNT_CENTS},
            paymentDate:      new \DateTimeImmutable($model->{PaymentTable::PAYMENT_DATE}->format('Y-m-d')),
            billingMonth:     (string) $model->{PaymentTable::BILLING_MONTH},
            notes:            $model->{PaymentTable::NOTES} ?? null,
            createdAt:        $model->created_at ? new \DateTimeImmutable((string) $model->created_at) : new \DateTimeImmutable(),
        );
    }

    public function dehydrate(Payment $payment): array
    {
        return [
            PaymentTable::ID                 => $payment->id->value(),
            PaymentTable::MEMBER_ID          => $payment->memberId->value(),
            PaymentTable::MEMBERSHIP_PLAN_ID => $payment->membershipPlanId->value(),
            PaymentTable::RECORDED_BY        => $payment->recordedBy->value(),
            PaymentTable::AMOUNT_CENTS       => $payment->amountCents,
            PaymentTable::PAYMENT_DATE       => $payment->paymentDate->format('Y-m-d'),
            PaymentTable::BILLING_MONTH      => $payment->billingMonth,
            PaymentTable::NOTES              => $payment->notes,
        ];
    }
}
