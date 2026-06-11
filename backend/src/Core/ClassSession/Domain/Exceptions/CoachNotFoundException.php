<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Domain\Exceptions;

final class CoachNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("User with id '{$id}' is not a coach or does not exist");
    }
}
