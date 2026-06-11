<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Application\Commands\ChangePassword;

use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

final readonly class ChangePasswordCommand
{
    public function __construct(
        public UserId $userId,
        public string $currentPassword,
        public string $newPassword,
    ) {}
}
