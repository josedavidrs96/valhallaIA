<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Domain\ValueObjects;

use App\Src\Shared\Auth\Domain\Exceptions\InvalidUserEmailException;

final class UserEmail
{
    private readonly string $value;

    public function __construct(string $email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidUserEmailException("Invalid email address: {$email}");
        }

        $this->value = mb_strtolower(trim($email));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
