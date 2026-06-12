<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Infrastructure\Hydrators;

use App\Src\Core\Member\Domain\Entities\MembershipPlan;
use App\Src\Core\Member\Domain\ValueObjects\MembershipPlanId;
use App\Src\Core\Member\Infrastructure\Persistence\MembershipPlanModel;
use App\Src\Core\Member\Infrastructure\Tables\MembershipPlanTable;

final class MembershipPlanHydrator
{
    public function hydrate(MembershipPlanModel $model): MembershipPlan
    {
        return new MembershipPlan(
            id:              MembershipPlanId::fromString($model->{MembershipPlanTable::ID}),
            name:            $model->{MembershipPlanTable::NAME},
            slug:            $model->{MembershipPlanTable::SLUG},
            priceCents:      (int) $model->{MembershipPlanTable::PRICE_CENTS},
            classesPerMonth:    $model->{MembershipPlanTable::CLASSES_PER_MONTH} !== null
                                    ? (int) $model->{MembershipPlanTable::CLASSES_PER_MONTH}
                                    : null,
            maxWeeklySessions:  (int) $model->{MembershipPlanTable::MAX_WEEKLY_SESSIONS},
            isActive:           (bool) $model->{MembershipPlanTable::IS_ACTIVE},
        );
    }
}
