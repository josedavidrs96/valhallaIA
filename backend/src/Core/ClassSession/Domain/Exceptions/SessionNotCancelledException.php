<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Domain\Exceptions;

final class SessionNotCancelledException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Session is not cancelled and cannot be restored');
    }
}
