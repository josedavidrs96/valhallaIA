<?php

declare(strict_types=1);

namespace App\Http\Actions\Payments\Shared;

use App\Src\Billing\Payment\Domain\ReadModels\PaymentListItemRM;
use Illuminate\Http\JsonResponse;

final class PaymentListResource
{
    public function __construct(
        private readonly array $items,
        private readonly int   $total,
        private readonly int   $page,
        private readonly int   $perPage,
    ) {}

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'data' => array_map(fn(PaymentListItemRM $rm) => [
                'id'            => $rm->id,
                'member_number' => $rm->memberNumber,
                'member_name'   => $rm->memberFirstName . ' ' . $rm->memberLastName,
                'plan_name'     => $rm->planName,
                'amount_cents'  => $rm->amountCents,
                'payment_date'  => $rm->paymentDate,
                'billing_month' => $rm->billingMonth,
                'created_at'    => $rm->createdAt,
            ], $this->items),
            'meta' => [
                'total'    => $this->total,
                'page'     => $this->page,
                'per_page' => $this->perPage,
            ],
        ]);
    }
}
