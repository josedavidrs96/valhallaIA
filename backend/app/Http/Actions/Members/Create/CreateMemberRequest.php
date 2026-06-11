<?php

declare(strict_types=1);

namespace App\Http\Actions\Members\Create;

use Illuminate\Foundation\Http\FormRequest;

final class CreateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function getDto(): CreateMemberDto
    {
        return new CreateMemberDto(
            email:       (string) $this->input('email', ''),
            password:    (string) $this->input('password', ''),
            firstName:   (string) $this->input('first_name', ''),
            lastName:    (string) $this->input('last_name', ''),
            planId:      (string) $this->input('membership_plan_id', ''),
            joinDate:    new \DateTimeImmutable((string) $this->input('join_date', 'today')),
            phone:       $this->input('phone') ? (string) $this->input('phone') : null,
            dateOfBirth: $this->input('date_of_birth')
                             ? new \DateTimeImmutable((string) $this->input('date_of_birth'))
                             : null,
        );
    }
}
