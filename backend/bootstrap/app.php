<?php

use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\RequireAdminRole;
use App\Http\Middleware\RequireCoachRole;
use App\Http\Middleware\RequireMemberRole;
use App\Http\Middleware\RequireStaffRole;
use App\Src\Shared\Auth\Domain\Exceptions\UserNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role.admin'            => RequireAdminRole::class,
            'role.coach'            => RequireCoachRole::class,
            'role.member'           => RequireMemberRole::class,
            'role.staff'            => RequireStaffRole::class,
            'force.password.change' => ForcePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => 'Unauthenticated', 'code' => 'UNAUTHENTICATED'], 401);
            }
        });

        $exceptions->render(function (UserNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => $e->getMessage(), 'code' => 'NOT_FOUND'], 404);
            }
        });

        $exceptions->render(function (\DomainException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => $e->getMessage(), 'code' => 'DOMAIN_ERROR'], 422);
            }
        });
    })->create();
