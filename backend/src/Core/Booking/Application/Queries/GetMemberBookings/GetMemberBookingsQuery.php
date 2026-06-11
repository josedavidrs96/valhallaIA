<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Application\Queries\GetMemberBookings;

use App\Src\Core\Member\Domain\ValueObjects\MemberId;

final readonly class GetMemberBookingsQuery
{
    public function __construct(
        public MemberId $memberId,
    ) {}
}
