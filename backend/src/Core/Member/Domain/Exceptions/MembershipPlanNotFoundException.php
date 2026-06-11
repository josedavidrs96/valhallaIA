<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Domain\Exceptions;

final class MembershipPlanNotFoundException extends \RuntimeException
{
    public function __construct(string $message = 'Membership plan not found')
    {
        parent::__construct($message);
    }
}
