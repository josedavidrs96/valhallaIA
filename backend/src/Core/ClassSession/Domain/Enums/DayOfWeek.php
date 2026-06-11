<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Domain\Enums;

enum DayOfWeek: string
{
    case Monday    = 'monday';
    case Tuesday   = 'tuesday';
    case Wednesday = 'wednesday';
    case Thursday  = 'thursday';
    case Friday    = 'friday';

    public function sortOrder(): int
    {
        return match ($this) {
            self::Monday    => 1,
            self::Tuesday   => 2,
            self::Wednesday => 3,
            self::Thursday  => 4,
            self::Friday    => 5,
        };
    }
}
