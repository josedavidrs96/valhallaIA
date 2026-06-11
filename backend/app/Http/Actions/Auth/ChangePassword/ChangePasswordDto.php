<?php

declare(strict_types=1);

namespace App\Http\Actions\Auth\ChangePassword;

final readonly class ChangePasswordDto
{
    public function __construct(
        public string $currentPassword,
        public string $newPassword,
        public string $newPasswordConfirmation,
    ) {}
}
