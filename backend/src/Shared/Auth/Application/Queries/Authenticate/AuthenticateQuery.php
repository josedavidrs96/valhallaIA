<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Application\Queries\Authenticate;

final readonly class AuthenticateQuery
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $rememberMe,
    ) {}
}
