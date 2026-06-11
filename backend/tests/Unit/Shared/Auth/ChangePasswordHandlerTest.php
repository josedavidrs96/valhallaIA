<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Auth;

use App\Src\Shared\Auth\Application\Commands\ChangePassword\ChangePasswordCommand;
use App\Src\Shared\Auth\Application\Commands\ChangePassword\ChangePasswordHandler;
use App\Src\Shared\Auth\Domain\Entities\User;
use App\Src\Shared\Auth\Domain\Enums\UserRole;
use App\Src\Shared\Auth\Domain\Enums\UserStatus;
use App\Src\Shared\Auth\Domain\Exceptions\WeakPasswordException;
use App\Src\Shared\Auth\Domain\Exceptions\WrongCurrentPasswordException;
use App\Src\Shared\Auth\Domain\Repositories\UserRepositoryInterface;
use App\Src\Shared\Auth\Domain\ValueObjects\HashedPassword;
use App\Src\Shared\Auth\Domain\ValueObjects\UserEmail;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ChangePasswordHandlerTest extends TestCase
{
    private UserRepositoryInterface&MockObject $repo;
    private ChangePasswordHandler $handler;
    private User $user;
    private UserId $userId;

    protected function setUp(): void
    {
        $this->userId = UserId::random();
        $this->user   = new User(
            id:                 $this->userId,
            email:              new UserEmail('test@valhallagym.com'),
            password:           HashedPassword::fromPlainText('OldPass123'),
            role:               UserRole::Member,
            status:             UserStatus::Active,
            mustChangePassword: true,
            createdAt:          new \DateTimeImmutable(),
        );

        $this->repo    = $this->createMock(UserRepositoryInterface::class);
        $this->handler = new ChangePasswordHandler($this->repo);
    }

    public function test_correct_password_change_succeeds(): void
    {
        $this->repo->method('getById')->willReturn($this->user);
        $this->repo->expects($this->once())->method('save');

        $this->handler->handle(new ChangePasswordCommand($this->userId, 'OldPass123', 'NewPass456'));

        $this->assertTrue($this->user->password()->verify('NewPass456'));
        $this->assertFalse($this->user->mustChangePassword());
    }

    public function test_wrong_current_password_throws(): void
    {
        $this->expectException(WrongCurrentPasswordException::class);

        $this->repo->method('getById')->willReturn($this->user);

        $this->handler->handle(new ChangePasswordCommand($this->userId, 'WrongPass', 'NewPass456'));
    }

    public function test_weak_new_password_throws(): void
    {
        $this->expectException(WeakPasswordException::class);

        $this->repo->method('getById')->willReturn($this->user);

        $this->handler->handle(new ChangePasswordCommand($this->userId, 'OldPass123', 'short'));
    }

    public function test_same_password_throws(): void
    {
        $this->expectException(WeakPasswordException::class);

        $this->repo->method('getById')->willReturn($this->user);

        $this->handler->handle(new ChangePasswordCommand($this->userId, 'OldPass123', 'OldPass123'));
    }

    public function test_must_change_password_cleared_after_success(): void
    {
        $this->repo->method('getById')->willReturn($this->user);
        $this->repo->expects($this->once())->method('save');

        $this->assertTrue($this->user->mustChangePassword());

        $this->handler->handle(new ChangePasswordCommand($this->userId, 'OldPass123', 'NewPass456'));

        $this->assertFalse($this->user->mustChangePassword());
    }
}
