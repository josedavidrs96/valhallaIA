<?php

declare(strict_types=1);

namespace App\Http\Actions\Members\Update;

final readonly class UpdateMemberDto
{
    public function __construct(
        public string              $firstName,
        public string              $lastName,
        public ?string             $phone,
        public ?\DateTimeImmutable $dateOfBirth,
        public ?string             $emergencyContactName,
        public ?string             $emergencyContactPhone,
        public ?string             $notes,
        public ?string             $profilePhoto,
    ) {}
}
