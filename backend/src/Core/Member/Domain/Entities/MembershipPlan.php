<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Domain\Entities;

use App\Src\Core\Member\Domain\ValueObjects\MembershipPlanId;

final class MembershipPlan
{
    public function __construct(
        public readonly MembershipPlanId $id,
        public readonly string           $name,
        public readonly string           $slug,
        public readonly int              $priceCents,
        public readonly ?int             $classesPerMonth,
        public readonly bool             $isActive,
    ) {}
}
