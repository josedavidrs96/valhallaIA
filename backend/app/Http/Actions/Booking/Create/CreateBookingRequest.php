<?php

declare(strict_types=1);

namespace App\Http\Actions\Booking\Create;

use Illuminate\Foundation\Http\FormRequest;

final class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function getDto(): CreateBookingDto
    {
        return new CreateBookingDto(
            classSessionId: (string) $this->input('class_session_id'),
        );
    }
}
