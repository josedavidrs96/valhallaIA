<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Domain\Exceptions;

final class WeekendSessionNotAllowedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Weekend sessions are not allowed in the current schedule');
    }
}
