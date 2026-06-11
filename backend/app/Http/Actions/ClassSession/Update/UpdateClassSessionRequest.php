<?php

declare(strict_types=1);

namespace App\Http\Actions\ClassSession\Update;

use App\Src\Core\ClassSession\Application\DTOs\UpdateClassSessionDto;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateClassSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function getDto(): UpdateClassSessionDto
    {
        return new UpdateClassSessionDto(
            classTypeId: (string) $this->input('class_type_id', ''),
            coachId:     $this->input('coach_id') ? (string) $this->input('coach_id') : null,
            maxCapacity: (int)    $this->input('max_capacity', 0),
        );
    }
}
