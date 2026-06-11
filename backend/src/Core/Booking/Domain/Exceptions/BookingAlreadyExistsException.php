<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Domain\Exceptions;

final class BookingAlreadyExistsException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Booking already exists: {$id}");
    }
}
