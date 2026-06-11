<?php

declare(strict_types=1);

namespace App\Http\Actions\Payments\Overdue;

use App\Http\Actions\Payments\Shared\OverdueMemberResource;
use App\Src\Billing\Payment\Application\Queries\GetOverdueMembers\GetOverdueMembersHandler;
use App\Src\Billing\Payment\Application\Queries\GetOverdueMembers\GetOverdueMembersQuery;
use Illuminate\Http\JsonResponse;

final class GetOverdueMembersAction
{
    public function __construct(
        private readonly GetOverdueMembersHandler $handler,
    ) {}

    public function __invoke(): JsonResponse
    {
        $items = $this->handler->handle(new GetOverdueMembersQuery());

        return (new OverdueMemberResource($items))->toResponse();
    }
}
