<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireStaffRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = $request->user()?->role;

        if (!in_array($role, ['admin', 'coach'], true)) {
            return response()->json([
                'error' => 'Forbidden',
                'code'  => 'INSUFFICIENT_ROLE',
            ], 403);
        }

        return $next($request);
    }
}
