<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Domain\Enums;

enum UserRole: string
{
    case Admin  = 'admin';
    case Coach  = 'coach';
    case Member = 'member';
}
