<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Domain\ReadModels;

use App\Src\Shared\Auth\Domain\Enums\UserRole;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

final readonly class AuthTokenRM
{
    public function __construct(
        public UserId $userId,
        public string $token,
        public \DateTimeImmutable $expiresAt,
        public UserRole $role,
        public bool $mustChangePassword,
    ) {}
}
