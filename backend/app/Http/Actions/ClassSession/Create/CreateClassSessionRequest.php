<?php

declare(strict_types=1);

namespace App\Http\Actions\ClassSession\Create;

use App\Src\Core\ClassSession\Application\DTOs\CreateClassSessionDto;
use Illuminate\Foundation\Http\FormRequest;

final class CreateClassSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function getDto(): CreateClassSessionDto
    {
        return new CreateClassSessionDto(
            classTypeId: (string) $this->input('class_type_id', ''),
            coachId:     $this->input('coach_id') ? (string) $this->input('coach_id') : null,
            dayOfWeek:   (string) $this->input('day_of_week', ''),
            timeSlot:    (string) $this->input('time_slot', ''),
            maxCapacity: (int)    $this->input('max_capacity', 0),
        );
    }
}
