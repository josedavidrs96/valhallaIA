<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Application\Commands\CreateMember;

use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use App\Src\Core\Member\Domain\ValueObjects\MembershipPlanId;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

final class CreateMemberCommand
{
    public function __construct(
        public readonly MemberId         $memberId,
        public readonly UserId           $userId,
        public readonly string           $email,
        public readonly string           $firstName,
        public readonly string           $lastName,
        public readonly \DateTimeImmutable $joinDate,
        public readonly MembershipPlanId $planId,
        public readonly string           $plainPassword = '',
        public readonly ?string          $phone = null,
        public readonly ?\DateTimeImmutable $dateOfBirth = null,
    ) {}
}
