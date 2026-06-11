<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (bool) $user->must_change_password) {
            return response()->json([
                'error' => 'Password change required',
                'code'  => 'MUST_CHANGE_PASSWORD',
            ], 403);
        }

        return $next($request);
    }
}
