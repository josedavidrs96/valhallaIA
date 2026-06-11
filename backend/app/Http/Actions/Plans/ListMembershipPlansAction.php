<?php

declare(strict_types=1);

namespace App\Http\Actions\Plans;

use App\Src\Core\Member\Domain\Repositories\MembershipPlanRepositoryInterface;
use Illuminate\Http\JsonResponse;

final class ListMembershipPlansAction
{
    public function __construct(private readonly MembershipPlanRepositoryInterface $repository) {}

    public function __invoke(): JsonResponse
    {
        $plans = $this->repository->findAllActive();

        return response()->json([
            'data' => array_map(fn($plan) => [
                'id'               => $plan->id->value(),
                'name'             => $plan->name,
                'slug'             => $plan->slug,
                'price_cents'      => $plan->priceCents,
                'classes_per_month' => $plan->classesPerMonth,
            ], $plans),
        ]);
    }
}
