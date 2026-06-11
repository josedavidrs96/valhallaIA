<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Auth;

use App\Src\Shared\Auth\Domain\Entities\User;
use App\Src\Shared\Auth\Domain\Enums\UserRole;
use App\Src\Shared\Auth\Domain\Enums\UserStatus;
use App\Src\Shared\Auth\Domain\Exceptions\InvalidStatusTransitionException;
use App\Src\Shared\Auth\Domain\Exceptions\WeakPasswordException;
use App\Src\Shared\Auth\Domain\ValueObjects\HashedPassword;
use App\Src\Shared\Auth\Domain\ValueObjects\UserEmail;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    private function makeUser(UserRole $role = UserRole::Member, UserStatus $status = UserStatus::Active, bool $mustChange = false): User
    {
        return new User(
            id:                 UserId::random(),
            email:              new UserEmail('test@valhallagym.com'),
            password:           HashedPassword::fromPlainText('Password123'),
            role:               $role,
            status:             $status,
            mustChangePassword: $mustChange,
            createdAt:          new \DateTimeImmutable(),
        );
    }

    public function test_create_produces_active_user(): void
    {
        $user = User::create(UserId::random(), new UserEmail('u@t.com'), HashedPassword::fromPlainText('Pass1234'), UserRole::Member);

        $this->assertTrue($user->isActive());
        $this->assertFalse($user->mustChangePassword());
        $this->assertNull($user->deletedAt());
    }

    public function test_activate_from_inactive_succeeds(): void
    {
        $user = $this->makeUser(status: UserStatus::Inactive);
        $user->activate();

        $this->assertTrue($user->isActive());
    }

    public function test_activate_from_pending_approval_succeeds(): void
    {
        $user = $this->makeUser(status: UserStatus::PendingApproval);
        $user->activate();

        $this->assertTrue($user->isActive());
    }

    public function test_deactivate_from_active_succeeds(): void
    {
        $user = $this->makeUser();
        $user->deactivate();

        $this->assertFalse($user->isActive());
        $this->assertSame(UserStatus::Inactive, $user->status());
    }

    public function test_deactivate_from_inactive_throws(): void
    {
        $this->expectException(InvalidStatusTransitionException::class);

        $user = $this->makeUser(status: UserStatus::Inactive);
        $user->deactivate();
    }

    public function test_suspend_member_succeeds(): void
    {
        $user = $this->makeUser(UserRole::Member);
        $user->suspend();

        $this->assertSame(UserStatus::Suspended, $user->status());
    }

    public function test_suspend_admin_throws(): void
    {
        $this->expectException(\DomainException::class);

        $user = $this->makeUser(UserRole::Admin);
        $user->suspend();
    }

    public function test_approve_from_pending_succeeds(): void
    {
        $user = $this->makeUser(status: UserStatus::PendingApproval);
        $user->approve();

        $this->assertTrue($user->isActive());
    }

    public function test_reject_from_pending_sets_inactive(): void
    {
        $user = $this->makeUser(status: UserStatus::PendingApproval);
        $user->reject();

        $this->assertSame(UserStatus::Inactive, $user->status());
    }

    public function test_can_login_only_when_active(): void
    {
        $active    = $this->makeUser(status: UserStatus::Active);
        $inactive  = $this->makeUser(status: UserStatus::Inactive);
        $suspended = $this->makeUser(status: UserStatus::Suspended);
        $pending   = $this->makeUser(status: UserStatus::PendingApproval);

        $this->assertTrue($active->canLogin());
        $this->assertFalse($inactive->canLogin());
        $this->assertFalse($suspended->canLogin());
        $this->assertFalse($pending->canLogin());
    }

    public function test_change_password_updates_hash(): void
    {
        $user    = $this->makeUser();
        $newPass = 'NewPassword456';

        $user->changePassword(HashedPassword::fromPlainText($newPass), $newPass);

        $this->assertTrue($user->password()->verify($newPass));
    }

    public function test_change_password_to_short_throws(): void
    {
        $this->expectException(WeakPasswordException::class);

        $user = $this->makeUser();
        $user->changePassword(HashedPassword::fromPlainText('short'), 'short');
    }

    public function test_change_password_to_same_throws(): void
    {
        $this->expectException(WeakPasswordException::class);

        $user = $this->makeUser();
        $user->changePassword(HashedPassword::fromPlainText('Password123'), 'Password123');
    }

    public function test_clear_password_change_flag(): void
    {
        $user = $this->makeUser(mustChange: true);
        $this->assertTrue($user->mustChangePassword());

        $user->clearPasswordChangeFlag();
        $this->assertFalse($user->mustChangePassword());
    }
}
