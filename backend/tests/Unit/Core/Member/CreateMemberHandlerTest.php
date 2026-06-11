<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Member;

use App\Src\Core\Member\Application\Commands\CreateMember\CreateMemberCommand;
use App\Src\Core\Member\Application\Commands\CreateMember\CreateMemberHandler;
use App\Src\Core\Member\Domain\Entities\MembershipPlan;
use App\Src\Core\Member\Domain\Exceptions\MemberEmailAlreadyExistsException;
use App\Src\Core\Member\Domain\Exceptions\MembershipPlanNotFoundException;
use App\Src\Core\Member\Domain\Repositories\MemberRepositoryInterface;
use App\Src\Core\Member\Domain\Repositories\MembershipPlanRepositoryInterface;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use App\Src\Core\Member\Domain\ValueObjects\MembershipPlanId;
use App\Src\Shared\Auth\Domain\Entities\User;
use App\Src\Shared\Auth\Domain\Enums\UserRole;
use App\Src\Shared\Auth\Domain\Repositories\UserRepositoryInterface;
use App\Src\Shared\Auth\Domain\ValueObjects\HashedPassword;
use App\Src\Shared\Auth\Domain\ValueObjects\UserEmail;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateMemberHandlerTest extends TestCase
{
    private UserRepositoryInterface&MockObject         $userRepo;
    private MemberRepositoryInterface&MockObject       $memberRepo;
    private MembershipPlanRepositoryInterface&MockObject $planRepo;
    private CreateMemberHandler                        $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepo   = $this->createMock(UserRepositoryInterface::class);
        $this->memberRepo = $this->createMock(MemberRepositoryInterface::class);
        $this->planRepo   = $this->createMock(MembershipPlanRepositoryInterface::class);

        $this->handler = new CreateMemberHandler(
            $this->userRepo,
            $this->memberRepo,
            $this->planRepo,
        );
    }

    private function makeCommand(string $email = 'new@gym.com'): CreateMemberCommand
    {
        return new CreateMemberCommand(
            memberId:    MemberId::random(),
            userId:      UserId::random(),
            email:       $email,
            firstName:   'Carlos',
            lastName:    'Ruiz',
            joinDate:    new \DateTimeImmutable('2026-06-10'),
            planId:      MembershipPlanId::random(),
        );
    }

    private function makeActivePlan(): MembershipPlan
    {
        return new MembershipPlan(
            id:              MembershipPlanId::random(),
            name:            'Plan 4-5 Dias',
            slug:            'plan-4-5-dias',
            priceCents:      4000,
            classesPerMonth: 25,
            isActive:        true,
        );
    }

    public function test_throws_email_already_exists_when_user_exists(): void
    {
        $existingUser = new User(
            id:                 UserId::random(),
            email:              new UserEmail('existing@gym.com'),
            password:           HashedPassword::fromPlainText('Password123'),
            role:               UserRole::Member,
            status:             \App\Src\Shared\Auth\Domain\Enums\UserStatus::Active,
            mustChangePassword: false,
            createdAt:          new \DateTimeImmutable(),
        );

        $this->userRepo
            ->method('findByEmail')
            ->willReturn($existingUser);

        $this->expectException(MemberEmailAlreadyExistsException::class);

        $this->handler->handle($this->makeCommand());
    }

    public function test_throws_plan_not_found_when_plan_missing(): void
    {
        $this->userRepo
            ->method('findByEmail')
            ->willReturn(null);

        $this->planRepo
            ->method('getById')
            ->willThrowException(new MembershipPlanNotFoundException());

        $this->expectException(MembershipPlanNotFoundException::class);

        $this->handler->handle($this->makeCommand());
    }

    public function test_throws_plan_not_found_when_plan_inactive(): void
    {
        $inactivePlan = new MembershipPlan(
            id:              MembershipPlanId::random(),
            name:            'Old Plan',
            slug:            'old-plan',
            priceCents:      2000,
            classesPerMonth: null,
            isActive:        false,
        );

        $this->userRepo
            ->method('findByEmail')
            ->willReturn(null);

        $this->planRepo
            ->method('getById')
            ->willReturn($inactivePlan);

        $this->expectException(MembershipPlanNotFoundException::class);

        $this->handler->handle($this->makeCommand());
    }
}
