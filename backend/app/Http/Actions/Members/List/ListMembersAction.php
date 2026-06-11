<?php

declare(strict_types=1);

namespace App\Http\Actions\Members\List;

use App\Http\Actions\Members\Shared\MemberListResource;
use App\Src\Core\Member\Application\Queries\ListMembers\ListMembersHandler;
use App\Src\Core\Member\Application\Queries\ListMembers\ListMembersQuery;
use Illuminate\Http\JsonResponse;

final class ListMembersAction
{
    public function __construct(private readonly ListMembersHandler $handler) {}

    public function __invoke(ListMembersRequest $request): JsonResponse
    {
        $dto    = $request->getDto();
        $result = $this->handler->handle(new ListMembersQuery(
            status:  $dto->status,
            planId:  $dto->planId,
            page:    $dto->page,
            perPage: $dto->perPage,
        ));

        return (new MemberListResource(
            items:   $result['items'],
            total:   $result['total'],
            page:    $result['page'],
            perPage: $result['perPage'],
        ))->toResponse();
    }
}
