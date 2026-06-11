<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Application\Queries\ListMembers;

use App\Src\Core\Member\Domain\Repositories\MemberRepositoryInterface;

final class ListMembersHandler
{
    public function __construct(private readonly MemberRepositoryInterface $memberRepository) {}

    public function handle(ListMembersQuery $query): array
    {
        $items = $this->memberRepository->findAll(
            status:  $query->status,
            planId:  $query->planId,
            page:    $query->page,
            perPage: $query->perPage,
        );

        $total = $this->memberRepository->countAll(
            status: $query->status,
            planId: $query->planId,
        );

        return [
            'items'    => $items,
            'total'    => $total,
            'page'     => $query->page,
            'perPage'  => $query->perPage,
        ];
    }
}
