<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Domain\Exceptions;

use App\Src\Shared\Auth\Domain\Enums\UserStatus;

final class InvalidStatusTransitionException extends \DomainException
{
    public function __construct(UserStatus $from, string $transition)
    {
        parent::__construct("Cannot '{$transition}' a user with status '{$from->value}'");
    }
}
