<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Domain\Enums;

enum UserStatus: string
{
    case Active           = 'active';
    case Inactive         = 'inactive';
    case Suspended        = 'suspended';
    case PendingApproval  = 'pending_approval';

    public function canLogin(): bool
    {
        return $this === self::Active;
    }
}
