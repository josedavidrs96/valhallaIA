<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Domain\Events;

use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

final readonly class UserPasswordChangedEvent
{
    public function __construct(public UserId $userId) {}
}
