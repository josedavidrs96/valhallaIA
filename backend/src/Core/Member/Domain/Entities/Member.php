<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Domain\Entities;

use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

final class Member
{
    public function __construct(
        public readonly MemberId             $id,
        public readonly UserId               $userId,
        public readonly int                  $memberNumber,
        public readonly string               $firstName,
        public readonly string               $lastName,
        public readonly ?string              $phone,
        public readonly ?\DateTimeImmutable  $dateOfBirth,
        public readonly ?string              $profilePhoto,
        public readonly \DateTimeImmutable   $joinDate,
        public readonly ?string              $emergencyContactName,
        public readonly ?string              $emergencyContactPhone,
        public readonly ?string              $notes,
        public readonly \DateTimeImmutable   $createdAt,
    ) {}

    public static function create(
        MemberId           $id,
        UserId             $userId,
        int                $memberNumber,
        string             $firstName,
        string             $lastName,
        \DateTimeImmutable $joinDate,
        ?string            $phone = null,
        ?\DateTimeImmutable $dateOfBirth = null,
    ): self {
        return new self(
            id:                   $id,
            userId:               $userId,
            memberNumber:         $memberNumber,
            firstName:            $firstName,
            lastName:             $lastName,
            phone:                $phone,
            dateOfBirth:          $dateOfBirth,
            profilePhoto:         null,
            joinDate:             $joinDate,
            emergencyContactName: null,
            emergencyContactPhone: null,
            notes:                null,
            createdAt:            new \DateTimeImmutable(),
        );
    }

    public function update(
        string  $firstName,
        string  $lastName,
        ?string $phone,
        ?\DateTimeImmutable $dateOfBirth,
        ?string $emergencyContactName,
        ?string $emergencyContactPhone,
        ?string $notes,
        ?string $profilePhoto,
    ): self {
        return new self(
            id:                   $this->id,
            userId:               $this->userId,
            memberNumber:         $this->memberNumber,
            firstName:            $firstName,
            lastName:             $lastName,
            phone:                $phone,
            dateOfBirth:          $dateOfBirth,
            profilePhoto:         $profilePhoto,
            joinDate:             $this->joinDate,
            emergencyContactName: $emergencyContactName,
            emergencyContactPhone: $emergencyContactPhone,
            notes:                $notes,
            createdAt:            $this->createdAt,
        );
    }
}
