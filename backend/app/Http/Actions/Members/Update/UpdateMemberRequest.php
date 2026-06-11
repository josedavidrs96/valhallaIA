<?php

declare(strict_types=1);

namespace App\Http\Actions\Members\Update;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function getDto(): UpdateMemberDto
    {
        return new UpdateMemberDto(
            firstName:            (string) $this->input('first_name', ''),
            lastName:             (string) $this->input('last_name', ''),
            phone:                $this->input('phone') ? (string) $this->input('phone') : null,
            dateOfBirth:          $this->input('date_of_birth')
                                      ? new \DateTimeImmutable((string) $this->input('date_of_birth'))
                                      : null,
            emergencyContactName: $this->input('emergency_contact_name')
                                      ? (string) $this->input('emergency_contact_name')
                                      : null,
            emergencyContactPhone: $this->input('emergency_contact_phone')
                                       ? (string) $this->input('emergency_contact_phone')
                                       : null,
            notes:                $this->input('notes') ? (string) $this->input('notes') : null,
            profilePhoto:         $this->input('profile_photo') ? (string) $this->input('profile_photo') : null,
        );
    }
}
