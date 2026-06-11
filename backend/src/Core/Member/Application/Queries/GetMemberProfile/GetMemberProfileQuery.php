<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Application\Queries\GetMemberProfile;

use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

final class GetMemberProfileQuery
{
    public function __construct(public readonly UserId $userId) {}
}
