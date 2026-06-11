<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Application\Commands\UpdateMember;

use App\Src\Core\Member\Domain\Repositories\MemberRepositoryInterface;

final class UpdateMemberHandler
{
    public function __construct(private readonly MemberRepositoryInterface $memberRepository) {}

    public function handle(UpdateMemberCommand $command): void
    {
        $member = $this->memberRepository->getById($command->memberId);

        $updated = $member->update(
            firstName:            $command->firstName,
            lastName:             $command->lastName,
            phone:                $command->phone,
            dateOfBirth:          $command->dateOfBirth,
            emergencyContactName: $command->emergencyContactName,
            emergencyContactPhone: $command->emergencyContactPhone,
            notes:                $command->notes,
            profilePhoto:         $command->profilePhoto,
        );

        $this->memberRepository->save($updated);
    }
}
