<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Domain\Entities;

use App\Src\Shared\Auth\Domain\Enums\UserRole;
use App\Src\Shared\Auth\Domain\Enums\UserStatus;
use App\Src\Shared\Auth\Domain\Exceptions\InvalidStatusTransitionException;
use App\Src\Shared\Auth\Domain\Exceptions\WeakPasswordException;
use App\Src\Shared\Auth\Domain\ValueObjects\HashedPassword;
use App\Src\Shared\Auth\Domain\ValueObjects\UserEmail;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

final class User
{
    private HashedPassword $password;
    private UserStatus $status;
    private bool $mustChangePassword;
    private ?\DateTimeImmutable $deletedAt;

    public function __construct(
        public readonly UserId $id,
        public readonly UserEmail $email,
        HashedPassword $password,
        public readonly UserRole $role,
        UserStatus $status,
        bool $mustChangePassword,
        public readonly \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $deletedAt = null,
    ) {
        $this->password           = $password;
        $this->status             = $status;
        $this->mustChangePassword = $mustChangePassword;
        $this->deletedAt          = $deletedAt;
    }

    public static function create(
        UserId $id,
        UserEmail $email,
        HashedPassword $password,
        UserRole $role,
        bool $mustChangePassword = false,
    ): self {
        return new self(
            id:                 $id,
            email:              $email,
            password:           $password,
            role:               $role,
            status:             UserStatus::Active,
            mustChangePassword: $mustChangePassword,
            createdAt:          new \DateTimeImmutable(),
        );
    }

    public function password(): HashedPassword
    {
        return $this->password;
    }

    public function status(): UserStatus
    {
        return $this->status;
    }

    public function mustChangePassword(): bool
    {
        return $this->mustChangePassword;
    }

    public function deletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function canLogin(): bool
    {
        return $this->status->canLogin();
    }

    public function changePassword(HashedPassword $new, string $newPlain): void
    {
        if (mb_strlen($newPlain) < 8) {
            throw new WeakPasswordException();
        }

        if ($this->password->isSameAs($newPlain)) {
            throw new WeakPasswordException('New password must differ from the current password');
        }

        $this->password = $new;
    }

    public function clearPasswordChangeFlag(): void
    {
        $this->mustChangePassword = false;
    }

    public function activate(): void
    {
        if (!in_array($this->status, [UserStatus::Inactive, UserStatus::PendingApproval], true)) {
            throw new InvalidStatusTransitionException($this->status, 'activate');
        }

        $this->status = UserStatus::Active;
    }

    public function deactivate(): void
    {
        if ($this->status !== UserStatus::Active) {
            throw new InvalidStatusTransitionException($this->status, 'deactivate');
        }

        $this->status = UserStatus::Inactive;
    }

    public function suspend(): void
    {
        if ($this->role !== UserRole::Member) {
            throw new \DomainException('Only members can be suspended');
        }

        if ($this->status !== UserStatus::Active) {
            throw new InvalidStatusTransitionException($this->status, 'suspend');
        }

        $this->status = UserStatus::Suspended;
    }

    public function approve(): void
    {
        if ($this->status !== UserStatus::PendingApproval) {
            throw new InvalidStatusTransitionException($this->status, 'approve');
        }

        $this->status = UserStatus::Active;
    }

    public function reject(): void
    {
        if ($this->status !== UserStatus::PendingApproval) {
            throw new InvalidStatusTransitionException($this->status, 'reject');
        }

        $this->status = UserStatus::Inactive;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
    }
}
