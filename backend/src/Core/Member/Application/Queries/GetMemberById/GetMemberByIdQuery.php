<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Application\Queries\GetMemberById;

use App\Src\Core\Member\Domain\ValueObjects\MemberId;

final class GetMemberByIdQuery
{
    public function __construct(public readonly MemberId $memberId) {}
}
