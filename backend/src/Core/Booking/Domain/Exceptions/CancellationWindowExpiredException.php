<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Domain\Exceptions;

final class CancellationWindowExpiredException extends \RuntimeException
{
    public function __construct(string $bookingId)
    {
        parent::__construct("Cancellation window has expired for booking '{$bookingId}'");
    }
}
