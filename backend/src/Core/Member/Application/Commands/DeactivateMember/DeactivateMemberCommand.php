<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Application\Commands\DeactivateMember;

use App\Src\Core\Member\Domain\ValueObjects\MemberId;

final class DeactivateMemberCommand
{
    public function __construct(public readonly MemberId $memberId) {}
}
