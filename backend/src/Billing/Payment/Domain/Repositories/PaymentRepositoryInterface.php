<?php

declare(strict_types=1);

namespace App\Src\Billing\Payment\Domain\Repositories;

use App\Src\Billing\Payment\Domain\Entities\Payment;
use App\Src\Billing\Payment\Domain\ReadModels\MemberPaymentListItemRM;
use App\Src\Billing\Payment\Domain\ReadModels\OverdueMemberRM;
use App\Src\Billing\Payment\Domain\ReadModels\PaymentDetailRM;
use App\Src\Billing\Payment\Domain\ReadModels\PaymentListItemRM;
use App\Src\Billing\Payment\Domain\ValueObjects\PaymentId;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;

interface PaymentRepositoryInterface
{
    public function getById(PaymentId $id): Payment;

    public function getDetailById(PaymentId $id): PaymentDetailRM;

    public function findByMemberAndBillingMonth(MemberId $memberId, string $billingMonth): ?Payment;

    public function save(Payment $payment): void;

    /**
     * @return PaymentListItemRM[]
     */
    public function findAll(?string $memberId, ?int $year, ?int $month, int $page, int $perPage): array;

    public function countAll(?string $memberId, ?int $year, ?int $month): int;

    /**
     * @return OverdueMemberRM[]
     */
    public function findOverdueMembers(string $billingMonth): array;

    /**
     * @return MemberPaymentListItemRM[]
     */
    public function findByMemberId(MemberId $memberId): array;
}
