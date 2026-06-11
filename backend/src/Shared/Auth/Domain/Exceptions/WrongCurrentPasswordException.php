<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Domain\Exceptions;

final class WrongCurrentPasswordException extends \DomainException
{
    public function __construct(string $message = 'Current password is incorrect')
    {
        parent::__construct($message);
    }
}
