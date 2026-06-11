<?php

declare(strict_types=1);

namespace App\Http\Actions\Auth\ChangePassword;

use Illuminate\Foundation\Http\FormRequest;

final class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function getDto(): ChangePasswordDto
    {
        return new ChangePasswordDto(
            currentPassword:         (string) $this->input('current_password', ''),
            newPassword:             (string) $this->input('new_password', ''),
            newPasswordConfirmation: (string) $this->input('new_password_confirmation', ''),
        );
    }
}
