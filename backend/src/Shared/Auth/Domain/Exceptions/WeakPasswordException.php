<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Domain\Exceptions;

final class WeakPasswordException extends \DomainException
{
    public function __construct(string $message = 'Password must be at least 8 characters long')
    {
        parent::__construct($message);
    }
}
