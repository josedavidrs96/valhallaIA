<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Domain\Exceptions;

use App\Src\Shared\Auth\Domain\Enums\UserStatus;

final class UserCannotLoginException extends \DomainException
{
    public function __construct(UserStatus $status)
    {
        $message = match ($status) {
            UserStatus::Inactive        => 'Your account is inactive. Please contact an administrator.',
            UserStatus::Suspended       => 'Your account has been suspended. Please contact an administrator.',
            UserStatus::PendingApproval => 'Your account is pending approval.',
            default                     => 'Your account cannot log in.',
        };

        parent::__construct($message);
    }
}
