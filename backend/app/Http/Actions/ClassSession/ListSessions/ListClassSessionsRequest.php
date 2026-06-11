<?php

declare(strict_types=1);

namespace App\Http\Actions\ClassSession\ListSessions;

use App\Src\Core\ClassSession\Application\DTOs\ListClassSessionsDto;
use Illuminate\Foundation\Http\FormRequest;

final class ListClassSessionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function getDto(): ListClassSessionsDto
    {
        return new ListClassSessionsDto(
            dayOfWeek: $this->input('day_of_week') ? (string) $this->input('day_of_week') : null,
            coachId:   $this->input('coach_id') ? (string) $this->input('coach_id') : null,
            status:    $this->input('status') ? (string) $this->input('status') : null,
        );
    }
}
