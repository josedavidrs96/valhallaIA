<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Application\Commands\Logout;

use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

final readonly class LogoutCommand
{
    public function __construct(
        public UserId $userId,
        public int $tokenId,
    ) {}
}
