<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Domain\ReadModels;

final readonly class MemberDetailRM
{
    public function __construct(
        public string  $id,
        public string  $userId,
        public int     $memberNumber,
        public string  $firstName,
        public string  $lastName,
        public string  $email,
        public ?string $phone,
        public ?string $dateOfBirth,
        public ?string $profilePhoto,
        public string  $joinDate,
        public string  $status,
        public ?string $planId,
        public ?string $planName,
        public ?int    $planPriceCents,
        public ?int    $planClassesPerMonth,
        public ?string $createdAt,
        public ?string $emergencyContactName,
        public ?string $emergencyContactPhone,
        public ?string $notes,
    ) {}
}
