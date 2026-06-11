<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Domain\Exceptions;

final class MemberEmailAlreadyExistsException extends \DomainException
{
    public function __construct(string $message = 'A member with this email already exists')
    {
        parent::__construct($message);
    }
}
