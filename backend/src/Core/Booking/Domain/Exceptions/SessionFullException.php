<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Domain\Exceptions;

final class SessionFullException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Session is full: {$id}");
    }
}
