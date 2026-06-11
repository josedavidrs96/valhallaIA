<?php

declare(strict_types=1);

namespace App\Http\Actions\Members\AssignPlan;

final readonly class AssignMemberPlanDto
{
    public function __construct(public string $planId) {}
}
