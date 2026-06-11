<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Domain\ReadModels;

final readonly class MemberListItemRM
{
    public function __construct(
        public string  $id,
        public int     $memberNumber,
        public string  $firstName,
        public string  $lastName,
        public string  $email,
        public string  $status,
        public ?string $planName,
        public ?string $planId,
        public string  $joinDate,
    ) {}
}
