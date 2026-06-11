<?php

declare(strict_types=1);

namespace App\Http\Actions\Auth\Me;

use App\Src\Shared\Auth\Domain\ReadModels\AuthUserRM;
use Illuminate\Http\JsonResponse;

final class CurrentUserResource
{
    public function __construct(private readonly AuthUserRM $rm) {}

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'id'                   => $this->rm->id->value(),
            'email'                => $this->rm->email,
            'role'                 => $this->rm->role->value,
            'status'               => $this->rm->status->value,
            'must_change_password' => $this->rm->mustChangePassword,
        ]);
    }
}
