<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Domain\Exceptions;

final class InvalidCredentialsException extends \DomainException
{
    public function __construct(string $message = 'Invalid credentials')
    {
        parent::__construct($message);
    }
}
