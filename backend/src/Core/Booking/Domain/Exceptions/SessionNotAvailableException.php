<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Domain\Exceptions;

final class SessionNotAvailableException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Session not available: {$id}");
    }
}
