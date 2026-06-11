<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Domain\Exceptions;

final class SessionHasBookingsException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Session cannot be deleted because it has existing bookings');
    }
}
