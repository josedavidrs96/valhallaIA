<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Application\Queries\ListMembers;

final class ListMembersQuery
{
    public function __construct(
        public readonly ?string $status  = null,
        public readonly ?string $planId  = null,
        public readonly int     $page    = 1,
        public readonly int     $perPage = 20,
    ) {}
}
