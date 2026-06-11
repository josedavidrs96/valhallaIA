<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Application\Commands\ActivateMember;

use App\Src\Core\Member\Domain\ValueObjects\MemberId;

final class ActivateMemberCommand
{
    public function __construct(public readonly MemberId $memberId) {}
}
