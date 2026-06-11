<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Domain\Exceptions;

final class CoachConflictException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('The coach already has a session assigned at the same day and time slot');
    }
}
