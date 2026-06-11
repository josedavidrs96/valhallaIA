<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Domain\ValueObjects;

final class HashedPassword
{
    public function __construct(private readonly string $hash) {}

    public static function fromPlainText(string $plain): self
    {
        return new self(password_hash($plain, PASSWORD_BCRYPT));
    }

    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    public function verify(string $plain): bool
    {
        return password_verify($plain, $this->hash);
    }

    public function isSameAs(string $plain): bool
    {
        return $this->verify($plain);
    }

    public function value(): string
    {
        return $this->hash;
    }
}
