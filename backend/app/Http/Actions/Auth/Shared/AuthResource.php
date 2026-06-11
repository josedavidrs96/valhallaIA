<?php

declare(strict_types=1);

namespace App\Http\Actions\Auth\Shared;

use App\Src\Shared\Auth\Domain\ReadModels\AuthTokenRM;
use Illuminate\Http\JsonResponse;

final class AuthResource
{
    public function __construct(private readonly AuthTokenRM $rm) {}

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'token'      => $this->rm->token,
            'expires_at' => $this->rm->expiresAt->format(\DateTimeInterface::ATOM),
            'user'       => [
                'id'                   => $this->rm->userId->value(),
                'role'                 => $this->rm->role->value,
                'must_change_password' => $this->rm->mustChangePassword,
            ],
        ]);
    }
}
