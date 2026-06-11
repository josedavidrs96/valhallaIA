<?php

declare(strict_types=1);

namespace App\Http\Actions\Members\Shared;

use App\Src\Core\Member\Domain\ReadModels\MemberListItemRM;
use Illuminate\Http\JsonResponse;

final class MemberListResource
{
    /**
     * @param MemberListItemRM[] $items
     */
    public function __construct(
        private readonly array $items,
        private readonly int   $total,
        private readonly int   $page,
        private readonly int   $perPage,
    ) {}

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'data' => array_map(fn(MemberListItemRM $item) => [
                'id'            => $item->id,
                'member_number' => $item->memberNumber,
                'first_name'    => $item->firstName,
                'last_name'     => $item->lastName,
                'email'         => $item->email,
                'status'        => $item->status,
                'join_date'     => $item->joinDate,
                'plan'          => $item->planId ? [
                    'id'   => $item->planId,
                    'name' => $item->planName,
                ] : null,
            ], $this->items),
            'meta' => [
                'total'    => $this->total,
                'page'     => $this->page,
                'per_page' => $this->perPage,
            ],
        ]);
    }
}
