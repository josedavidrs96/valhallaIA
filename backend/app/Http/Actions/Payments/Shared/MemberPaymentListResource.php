<?php

declare(strict_types=1);

namespace App\Http\Actions\Payments\Shared;

use App\Src\Billing\Payment\Domain\ReadModels\MemberPaymentListItemRM;
use Illuminate\Http\JsonResponse;

final class MemberPaymentListResource
{
    public function __construct(private readonly array $items) {}

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'data' => array_map(fn(MemberPaymentListItemRM $rm) => [
                'id'            => $rm->id,
                'amount_cents'  => $rm->amountCents,
                'payment_date'  => $rm->paymentDate,
                'billing_month' => $rm->billingMonth,
                'plan_name'     => $rm->planName,
            ], $this->items),
        ]);
    }
}
