<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Application\Commands\CreateMember;

use App\Src\Core\Member\Domain\Entities\Member;
use App\Src\Core\Member\Domain\Exceptions\MemberEmailAlreadyExistsException;
use App\Src\Core\Member\Domain\Exceptions\MembershipPlanNotFoundException;
use App\Src\Core\Member\Domain\Repositories\MemberRepositoryInterface;
use App\Src\Core\Member\Domain\Repositories\MembershipPlanRepositoryInterface;
use App\Src\Core\Member\Infrastructure\Persistence\MemberPlanAssignmentModel;
use App\Src\Core\Member\Infrastructure\Tables\MemberPlanAssignmentTable;
use App\Src\Shared\Auth\Domain\Enums\UserRole;
use App\Src\Shared\Auth\Domain\Enums\UserStatus;
use App\Src\Shared\Auth\Domain\Repositories\UserRepositoryInterface;
use App\Src\Shared\Auth\Domain\ValueObjects\UserEmail;
use App\Src\Shared\Auth\Infrastructure\Persistence\UserModel;
use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Uid\Ulid;

final class CreateMemberHandler
{
    public function __construct(
        private readonly UserRepositoryInterface         $userRepository,
        private readonly MemberRepositoryInterface       $memberRepository,
        private readonly MembershipPlanRepositoryInterface $planRepository,
    ) {}

    public function handle(CreateMemberCommand $command): void
    {
        // 1. Check email uniqueness
        $existing = $this->userRepository->findByEmail(new UserEmail($command->email));
        if ($existing !== null) {
            throw new MemberEmailAlreadyExistsException(
                "A member with email '{$command->email}' already exists"
            );
        }

        // 2. Verify plan exists and is active
        $plan = $this->planRepository->getById($command->planId);
        if (!$plan->isActive) {
            throw new MembershipPlanNotFoundException(
                "Membership plan '{$command->planId->value()}' is not active"
            );
        }

        // 3. Atomic transaction: member number lock + User + Member + PlanAssignment
        DB::transaction(function () use ($command) {
            // Get next member number with lock inside the transaction to prevent races
            $memberNumber = $this->memberRepository->nextMemberNumber();

            // a. Create User
            UserModel::query()->create([
                UserTable::ID                   => $command->userId->value(),
                UserTable::EMAIL                => $command->email,
                UserTable::PASSWORD             => password_hash(
                    $command->plainPassword !== '' ? $command->plainPassword : Str::random(16),
                    PASSWORD_BCRYPT
                ),
                UserTable::ROLE                 => UserRole::Member->value,
                UserTable::STATUS               => UserStatus::PendingApproval->value,
                UserTable::MUST_CHANGE_PASSWORD => 1,
            ]);

            // b. Create Member entity and persist
            $member = Member::create(
                id:           $command->memberId,
                userId:       $command->userId,
                memberNumber: $memberNumber,
                firstName:    $command->firstName,
                lastName:     $command->lastName,
                joinDate:     $command->joinDate,
                phone:        $command->phone,
                dateOfBirth:  $command->dateOfBirth,
            );

            $this->memberRepository->save($member);

            // c. Create plan assignment (append-only history)
            MemberPlanAssignmentModel::query()->create([
                MemberPlanAssignmentTable::ID                 => (string) new Ulid(),
                MemberPlanAssignmentTable::MEMBER_ID          => $command->memberId->value(),
                MemberPlanAssignmentTable::MEMBERSHIP_PLAN_ID => $command->planId->value(),
                MemberPlanAssignmentTable::ASSIGNED_AT        => (new \DateTimeImmutable())->format('Y-m-d'),
            ]);
        });
    }
}
