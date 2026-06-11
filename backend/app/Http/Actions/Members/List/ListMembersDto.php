<?php

declare(strict_types=1);

namespace App\Http\Actions\Members\List;

final readonly class ListMembersDto
{
    public function __construct(
        public ?string $status,
        public ?string $planId,
        public ?string $search,
        public int     $page,
        public int     $perPage,
    ) {}
}
