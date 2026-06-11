<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Domain\Enums;

enum MemberStatus: string
{
    case PendingApproval = 'pending_approval';
    case Active          = 'active';
    case Inactive        = 'inactive';
}
