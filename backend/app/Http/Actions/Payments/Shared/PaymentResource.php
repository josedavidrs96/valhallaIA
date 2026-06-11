<?php

declare(strict_types=1);

namespace App\Http\Actions\Payments\Shared;

use App\Src\Billing\Payment\Domain\ReadModels\PaymentDetailRM;
use Illuminate\Http\JsonResponse;

final class PaymentResource
{
    public function __construct(private readonly PaymentDetailRM $rm) {}

    public function toResponse(int $status = 200): JsonResponse
    {
        return response()->json([
            'id'     => $this->rm->id,
            'member' => [
                'id'            => $this->rm->memberId,
                'member_number' => $this->rm->memberNumber,
                'name'          => $this->rm->memberFirstName . ' ' . $this->rm->memberLastName,
            ],
            'plan' => [
                'id'   => $this->rm->membershipPlanId,
                'name' => $this->rm->planName,
            ],
            'recorded_by'   => $this->rm->recordedBy,
            'amount_cents'  => $this->rm->amountCents,
            'payment_date'  => $this->rm->paymentDate,
            'billing_month' => $this->rm->billingMonth,
            'notes'         => $this->rm->notes,
            'created_at'    => $this->rm->createdAt,
        ], $status);
    }
}
