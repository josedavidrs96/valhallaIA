<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Domain\Repositories;

use App\Src\Core\Member\Domain\Entities\MembershipPlan;
use App\Src\Core\Member\Domain\Exceptions\MembershipPlanNotFoundException;
use App\Src\Core\Member\Domain\ValueObjects\MembershipPlanId;

interface MembershipPlanRepositoryInterface
{
    /**
     * @throws MembershipPlanNotFoundException
     */
    public function getById(MembershipPlanId $id): MembershipPlan;

    /**
     * @return MembershipPlan[]
     */
    public function findAllActive(): array;
}
