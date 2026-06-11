<?php

declare(strict_types=1);

namespace App\Src\Billing\Payment\Application\Commands\RecordPayment;

use App\Src\Billing\Payment\Domain\Entities\Payment;
use App\Src\Billing\Payment\Domain\Exceptions\PaymentAlreadyExistsForMonthException;
use App\Src\Billing\Payment\Domain\Repositories\PaymentRepositoryInterface;
use App\Src\Core\Member\Domain\Repositories\MemberRepositoryInterface;
use App\Src\Core\Member\Domain\Repositories\MembershipPlanRepositoryInterface;

final class RecordPaymentHandler
{
    public function __construct(
        private readonly PaymentRepositoryInterface      $paymentRepo,
        private readonly MemberRepositoryInterface       $memberRepo,
        private readonly MembershipPlanRepositoryInterface $planRepo,
    ) {}

    public function handle(RecordPaymentCommand $command): void
    {
        $billingMonth = $command->paymentDate->format('Y-m');

        if ($this->paymentRepo->findByMemberAndBillingMonth($command->memberId, $billingMonth) !== null) {
            throw new PaymentAlreadyExistsForMonthException($billingMonth);
        }

        // Throws MemberNotFoundException if member does not exist
        $this->memberRepo->getById($command->memberId);

        // Throws MembershipPlanNotFoundException if plan does not exist
        $this->planRepo->getById($command->membershipPlanId);

        $payment = Payment::create(
            $command->paymentId,
            $command->memberId,
            $command->membershipPlanId,
            $command->recordedBy,
            $command->amountCents,
            $command->paymentDate,
            $command->notes,
        );

        $this->paymentRepo->save($payment);
    }
}
