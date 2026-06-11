<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Domain\Exceptions;

final class ClassTypeNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Active class type with id '{$id}' not found");
    }
}
