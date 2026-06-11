<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Domain\ReadModels;

use App\Src\Shared\Auth\Domain\Enums\UserRole;
use App\Src\Shared\Auth\Domain\Enums\UserStatus;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

final readonly class AuthUserRM
{
    public function __construct(
        public UserId $id,
        public string $email,
        public UserRole $role,
        public UserStatus $status,
        public bool $mustChangePassword,
    ) {}
}
