<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Domain\Exceptions;

final class MemberHasNoPlanException extends \RuntimeException
{
    public function __construct(string $memberId)
    {
        parent::__construct("Member '{$memberId}' has no active plan assignment");
    }
}
