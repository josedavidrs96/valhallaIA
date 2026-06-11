<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Application\Commands\Logout;

use Laravel\Sanctum\PersonalAccessToken;

final class LogoutHandler
{
    public function handle(LogoutCommand $command): void
    {
        PersonalAccessToken::where('id', $command->tokenId)->delete();
    }
}
