<?php

declare(strict_types=1);

namespace App\Http\Actions\Members\Create;

final readonly class CreateMemberDto
{
    public function __construct(
        public string              $email,
        public string              $password,
        public string              $firstName,
        public string              $lastName,
        public string              $planId,
        public \DateTimeImmutable  $joinDate,
        public ?string             $phone,
        public ?\DateTimeImmutable $dateOfBirth,
    ) {}
}
