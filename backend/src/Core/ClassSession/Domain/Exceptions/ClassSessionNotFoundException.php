<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Domain\Exceptions;

final class ClassSessionNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Class session with id '{$id}' not found");
    }
}
