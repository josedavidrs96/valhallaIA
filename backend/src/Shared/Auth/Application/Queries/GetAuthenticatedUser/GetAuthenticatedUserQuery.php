<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Application\Queries\GetAuthenticatedUser;

use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

final readonly class GetAuthenticatedUserQuery
{
    public function __construct(public UserId $userId) {}
}
