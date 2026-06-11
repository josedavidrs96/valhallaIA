<?php

declare(strict_types=1);

namespace App\Http\Actions\Payments\List;

use Illuminate\Foundation\Http\FormRequest;

final class ListPaymentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function getDto(): ListPaymentsDto
    {
        return new ListPaymentsDto(
            memberId: $this->input('member_id') ? (string) $this->input('member_id') : null,
            year:     $this->input('year') ? (int) $this->input('year') : null,
            month:    $this->input('month') ? (int) $this->input('month') : null,
            page:     (int) $this->input('page', 1),
            perPage:  (int) $this->input('per_page', 20),
        );
    }
}
