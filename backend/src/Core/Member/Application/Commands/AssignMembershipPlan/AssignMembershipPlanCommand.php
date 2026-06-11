<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Application\Commands\AssignMembershipPlan;

use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use App\Src\Core\Member\Domain\ValueObjects\MembershipPlanId;

final class AssignMembershipPlanCommand
{
    public function __construct(
        public readonly MemberId         $memberId,
        public readonly MembershipPlanId $planId,
    ) {}
}
