<?php

declare(strict_types=1);

namespace App\Http\Actions\Auth\Login;

final readonly class LoginDto
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $rememberMe,
    ) {}
}
