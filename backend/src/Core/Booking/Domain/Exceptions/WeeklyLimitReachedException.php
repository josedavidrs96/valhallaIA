<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Domain\Exceptions;

final class WeeklyLimitReachedException extends \RuntimeException
{
    public function __construct(
        public readonly int $used,
        public readonly int $max,
    ) {
        parent::__construct("Weekly booking limit reached: {$used}/{$max}");
    }
}
