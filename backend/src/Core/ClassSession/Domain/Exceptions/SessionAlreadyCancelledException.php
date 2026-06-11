<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Domain\Exceptions;

final class SessionAlreadyCancelledException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Session is already cancelled');
    }
}
