<?php

declare(strict_types=1);

namespace App\Src\Billing\Payment\Application\Queries\GetMyPayments;

use App\Src\Core\Member\Domain\ValueObjects\MemberId;

final readonly class GetMyPaymentsQuery
{
    public function __construct(
        public MemberId $memberId,
    ) {}
}
