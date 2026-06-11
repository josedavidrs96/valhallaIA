<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Domain\Enums;

enum ClassSessionStatus: string
{
    case Active    = 'active';
    case Cancelled = 'cancelled';
}
