<?php

declare(strict_types=1);

namespace App\Http\Actions\Auth\Login;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function getDto(): LoginDto
    {
        return new LoginDto(
            email:      (string) $this->input('email', ''),
            password:   (string) $this->input('password', ''),
            rememberMe: (bool)   $this->input('remember_me', false),
        );
    }
}
