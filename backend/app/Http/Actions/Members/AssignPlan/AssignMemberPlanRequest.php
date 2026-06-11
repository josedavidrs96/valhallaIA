<?php

declare(strict_types=1);

namespace App\Http\Actions\Members\AssignPlan;

use Illuminate\Foundation\Http\FormRequest;

final class AssignMemberPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function getDto(): AssignMemberPlanDto
    {
        return new AssignMemberPlanDto(
            planId: (string) $this->input('membership_plan_id', ''),
        );
    }
}
