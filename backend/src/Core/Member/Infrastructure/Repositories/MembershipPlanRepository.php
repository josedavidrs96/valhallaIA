<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Infrastructure\Repositories;

use App\Src\Core\Member\Domain\Entities\MembershipPlan;
use App\Src\Core\Member\Domain\Exceptions\MembershipPlanNotFoundException;
use App\Src\Core\Member\Domain\Repositories\MembershipPlanRepositoryInterface;
use App\Src\Core\Member\Domain\ValueObjects\MembershipPlanId;
use App\Src\Core\Member\Infrastructure\Hydrators\MembershipPlanHydrator;
use App\Src\Core\Member\Infrastructure\Persistence\MembershipPlanModel;
use App\Src\Core\Member\Infrastructure\Tables\MembershipPlanTable;

final class MembershipPlanRepository implements MembershipPlanRepositoryInterface
{
    public function __construct(private readonly MembershipPlanHydrator $hydrator) {}

    public function getById(MembershipPlanId $id): MembershipPlan
    {
        $model = MembershipPlanModel::query()
            ->where(MembershipPlanTable::ID, $id->value())
            ->first();

        if ($model === null) {
            throw new MembershipPlanNotFoundException("Membership plan with id '{$id->value()}' not found");
        }

        return $this->hydrator->hydrate($model);
    }

    /**
     * @return MembershipPlan[]
     */
    public function findAllActive(): array
    {
        $models = MembershipPlanModel::query()
            ->where(MembershipPlanTable::IS_ACTIVE, 1)
            ->get();

        return $models->map(fn($model) => $this->hydrator->hydrate($model))->all();
    }
}
