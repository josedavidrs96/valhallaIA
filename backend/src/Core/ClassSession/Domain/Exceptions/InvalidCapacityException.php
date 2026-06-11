<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Domain\Exceptions;

final class InvalidCapacityException extends \DomainException
{
    public function __construct(int $capacity)
    {
        parent::__construct("Max capacity must be at least 1, got {$capacity}");
    }
}
