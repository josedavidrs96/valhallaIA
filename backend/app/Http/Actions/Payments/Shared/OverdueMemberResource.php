<?php

declare(strict_types=1);

namespace App\Http\Actions\Payments\Shared;

use App\Src\Billing\Payment\Domain\ReadModels\OverdueMemberRM;
use Illuminate\Http\JsonResponse;

final class OverdueMemberResource
{
    public function __construct(private readonly array $items) {}

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'data' => array_map(fn(OverdueMemberRM $rm) => [
                'member_id'         => $rm->memberId,
                'member_number'     => $rm->memberNumber,
                'first_name'        => $rm->firstName,
                'last_name'         => $rm->lastName,
                'email'             => $rm->email,
                'plan_name'         => $rm->planName,
                'last_payment_date' => $rm->lastPaymentDate,
            ], $this->items),
            'meta' => [
                'total' => count($this->items),
            ],
        ]);
    }
}
