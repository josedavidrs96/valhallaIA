<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Member;

use App\Src\Core\Member\Application\Commands\ActivateMember\ActivateMemberCommand;
use App\Src\Core\Member\Application\Commands\ActivateMember\ActivateMemberHandler;
use App\Src\Core\Member\Domain\Entities\Member;
use App\Src\Core\Member\Domain\Repositories\MemberRepositoryInterface;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use App\Src\Shared\Auth\Domain\Entities\User;
use App\Src\Shared\Auth\Domain\Enums\UserRole;
use App\Src\Shared\Auth\Domain\Enums\UserStatus;
use App\Src\Shared\Auth\Domain\Exceptions\InvalidStatusTransitionException;
use App\Src\Shared\Auth\Domain\Repositories\UserRepositoryInterface;
use App\Src\Shared\Auth\Domain\ValueObjects\HashedPassword;
use App\Src\Shared\Auth\Domain\ValueObjects\UserEmail;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class MemberStatusHandlersTest extends TestCase
{
    private MemberRepositoryInterface&MockObject $memberRepo;
    private UserRepositoryInterface&MockObject   $userRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->memberRepo = $this->createMock(MemberRepositoryInterface::class);
        $this->userRepo   = $this->createMock(UserRepositoryInterface::class);
    }

    private function makeMember(UserId $userId): Member
    {
        return Member::create(
            id:           MemberId::random(),
            userId:       $userId,
            memberNumber: 1,
            firstName:    'Carlos',
            lastName:     'Ruiz',
            joinDate:     new \DateTimeImmutable(),
        );
    }

    private function makeUser(UserStatus $status): User
    {
        return new User(
            id:                 UserId::random(),
            email:              new UserEmail('u@test.com'),
            password:           HashedPassword::fromPlainText('Password123'),
            role:               UserRole::Member,
            status:             $status,
            mustChangePassword: false,
            createdAt:          new \DateTimeImmutable(),
        );
    }

    // -------------------------------------------------------------------------
    // Activate handler
    // -------------------------------------------------------------------------

    public function test_activate_pending_member_succeeds(): void
    {
        $user   = $this->makeUser(UserStatus::PendingApproval);
        $member = $this->makeMember($user->id);

        $this->memberRepo->method('getById')->willReturn($member);
        $this->userRepo->method('getById')->willReturn($user);
        $this->userRepo->expects($this->once())->method('save');

        $handler = new ActivateMemberHandler($this->memberRepo, $this->userRepo);
        $handler->handle(new ActivateMemberCommand($member->id));

        $this->assertSame(UserStatus::Active, $user->status());
    }

    public function test_activate_inactive_member_succeeds(): void
    {
        $user   = $this->makeUser(UserStatus::Inactive);
        $member = $this->makeMember($user->id);

        $this->memberRepo->method('getById')->willReturn($member);
        $this->userRepo->method('getById')->willReturn($user);
        $this->userRepo->expects($this->once())->method('save');

        $handler = new ActivateMemberHandler($this->memberRepo, $this->userRepo);
        $handler->handle(new ActivateMemberCommand($member->id));

        $this->assertSame(UserStatus::Active, $user->status());
    }

    public function test_activate_already_active_throws(): void
    {
        $user   = $this->makeUser(UserStatus::Active);
        $member = $this->makeMember($user->id);

        $this->memberRepo->method('getById')->willReturn($member);
        $this->userRepo->method('getById')->willReturn($user);

        $this->expectException(InvalidStatusTransitionException::class);

        $handler = new ActivateMemberHandler($this->memberRepo, $this->userRepo);
        $handler->handle(new ActivateMemberCommand($member->id));
    }

    // -------------------------------------------------------------------------
    // Deactivate handler
    // -------------------------------------------------------------------------

    public function test_deactivate_active_member_succeeds(): void
    {
        // DeactivateMemberHandler calls UserModel for token revocation — requires DB.
        // We test the domain behavior directly here; full E2E is covered in Feature tests.
        $user = $this->makeUser(UserStatus::Active);

        $user->deactivate();

        $this->assertSame(UserStatus::Inactive, $user->status());
    }

    public function test_deactivate_inactive_member_throws(): void
    {
        $this->expectException(InvalidStatusTransitionException::class);

        $user = $this->makeUser(UserStatus::Inactive);
        $user->deactivate(); // Must throw
    }
}
