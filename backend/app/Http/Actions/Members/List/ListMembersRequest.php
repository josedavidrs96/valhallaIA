<?php

declare(strict_types=1);

namespace App\Http\Actions\Members\List;

use Illuminate\Foundation\Http\FormRequest;

final class ListMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function getDto(): ListMembersDto
    {
        return new ListMembersDto(
            status:  $this->input('status'),
            planId:  $this->input('plan_id'),
            search:  $this->input('search'),
            page:    (int) $this->input('page', 1),
            perPage: (int) $this->input('per_page', 20),
        );
    }
}
