<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Domain\Exceptions;

final class DailyLimitReachedException extends \RuntimeException
{
    public function __construct(string $date)
    {
        parent::__construct("Daily booking limit reached for date: {$date}");
    }
}
