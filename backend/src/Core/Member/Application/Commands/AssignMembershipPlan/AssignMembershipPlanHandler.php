<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Application\Commands\AssignMembershipPlan;

use App\Src\Core\Member\Domain\Exceptions\MembershipPlanNotFoundException;
use App\Src\Core\Member\Domain\Repositories\MemberRepositoryInterface;
use App\Src\Core\Member\Domain\Repositories\MembershipPlanRepositoryInterface;
use App\Src\Core\Member\Infrastructure\Persistence\MemberPlanAssignmentModel;
use App\Src\Core\Member\Infrastructure\Tables\MemberPlanAssignmentTable;
use Symfony\Component\Uid\Ulid;

final class AssignMembershipPlanHandler
{
    public function __construct(
        private readonly MemberRepositoryInterface       $memberRepository,
        private readonly MembershipPlanRepositoryInterface $planRepository,
    ) {}

    public function handle(AssignMembershipPlanCommand $command): void
    {
        // Verify member exists
        $this->memberRepository->getById($command->memberId);

        // Verify plan is active
        $plan = $this->planRepository->getById($command->planId);
        if (!$plan->isActive) {
            throw new MembershipPlanNotFoundException(
                "Membership plan '{$command->planId->value()}' is not active"
            );
        }

        // Append-only: create new assignment
        MemberPlanAssignmentModel::query()->create([
            MemberPlanAssignmentTable::ID                 => (string) new Ulid(),
            MemberPlanAssignmentTable::MEMBER_ID          => $command->memberId->value(),
            MemberPlanAssignmentTable::MEMBERSHIP_PLAN_ID => $command->planId->value(),
            MemberPlanAssignmentTable::ASSIGNED_AT        => (new \DateTimeImmutable())->format('Y-m-d'),
        ]);
    }
}
