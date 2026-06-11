<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Application\Commands\UpdateMember;

use App\Src\Core\Member\Domain\ValueObjects\MemberId;

final class UpdateMemberCommand
{
    public function __construct(
        public readonly MemberId            $memberId,
        public readonly string              $firstName,
        public readonly string              $lastName,
        public readonly ?string             $phone,
        public readonly ?\DateTimeImmutable $dateOfBirth,
        public readonly ?string             $emergencyContactName,
        public readonly ?string             $emergencyContactPhone,
        public readonly ?string             $notes,
        public readonly ?string             $profilePhoto,
    ) {}
}
