<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Domain\ValueObjects;

use Symfony\Component\Uid\Ulid;

final class ClassSessionId extends Ulid
{
    public static function random(): static
    {
        return new static();
    }

    public static function fromString(string $value): static
    {
        return new static($value);
    }

    public function value(): string
    {
        return $this->toBase32();
    }
}
