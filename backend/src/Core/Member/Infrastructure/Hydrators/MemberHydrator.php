<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Infrastructure\Hydrators;

use App\Src\Core\Member\Domain\Entities\Member;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use App\Src\Core\Member\Infrastructure\Persistence\MemberModel;
use App\Src\Core\Member\Infrastructure\Tables\MemberTable;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

final class MemberHydrator
{
    public function hydrate(MemberModel $model): Member
    {
        return new Member(
            id:                   MemberId::fromString($model->{MemberTable::ID}),
            userId:               UserId::fromString($model->{MemberTable::USER_ID}),
            memberNumber:         (int) $model->{MemberTable::MEMBER_NUMBER},
            firstName:            $model->{MemberTable::FIRST_NAME},
            lastName:             $model->{MemberTable::LAST_NAME},
            phone:                $model->{MemberTable::PHONE},
            dateOfBirth:          $model->{MemberTable::DATE_OF_BIRTH}
                                      ? new \DateTimeImmutable((string) $model->{MemberTable::DATE_OF_BIRTH})
                                      : null,
            profilePhoto:         $model->{MemberTable::PROFILE_PHOTO},
            joinDate:             new \DateTimeImmutable((string) $model->{MemberTable::JOIN_DATE}),
            emergencyContactName: $model->{MemberTable::EMERGENCY_CONTACT_NAME},
            emergencyContactPhone: $model->{MemberTable::EMERGENCY_CONTACT_PHONE},
            notes:                $model->{MemberTable::NOTES},
            createdAt:            new \DateTimeImmutable((string) $model->{MemberTable::CREATED_AT}),
        );
    }

    public function dehydrate(Member $member): array
    {
        return [
            MemberTable::ID                    => $member->id->value(),
            MemberTable::USER_ID               => $member->userId->value(),
            MemberTable::MEMBER_NUMBER         => $member->memberNumber,
            MemberTable::FIRST_NAME            => $member->firstName,
            MemberTable::LAST_NAME             => $member->lastName,
            MemberTable::PHONE                 => $member->phone,
            MemberTable::DATE_OF_BIRTH         => $member->dateOfBirth?->format('Y-m-d'),
            MemberTable::PROFILE_PHOTO         => $member->profilePhoto,
            MemberTable::JOIN_DATE             => $member->joinDate->format('Y-m-d'),
            MemberTable::EMERGENCY_CONTACT_NAME  => $member->emergencyContactName,
            MemberTable::EMERGENCY_CONTACT_PHONE => $member->emergencyContactPhone,
            MemberTable::NOTES                 => $member->notes,
        ];
    }
}
