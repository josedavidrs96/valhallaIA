<?php

declare(strict_types=1);

use App\Http\Actions\Auth\ChangePassword\ChangePasswordAction;
use App\Http\Actions\Auth\Login\LoginAction;
use App\Http\Actions\Auth\Logout\LogoutAction;
use App\Http\Actions\Auth\Me\GetCurrentUserAction;
use App\Http\Actions\MemberProfile\GetMemberProfileAction;
use App\Http\Actions\Members\Activate\ActivateMemberAction;
use App\Http\Actions\Members\AssignPlan\AssignMemberPlanAction;
use App\Http\Actions\Members\Create\CreateMemberAction;
use App\Http\Actions\Members\Deactivate\DeactivateMemberAction;
use App\Http\Actions\Members\Detail\GetMemberDetailAction;
use App\Http\Actions\Members\List\ListMembersAction;
use App\Http\Actions\Members\Update\UpdateMemberAction;
use App\Http\Actions\ClassSession\Cancel\CancelClassSessionAction;
use App\Http\Actions\ClassSession\Coach\GetCoachSessionsAction;
use App\Http\Actions\ClassSession\Create\CreateClassSessionAction;
use App\Http\Actions\ClassSession\Delete\DeleteClassSessionAction;
use App\Http\Actions\ClassSession\Get\GetClassSessionAction;
use App\Http\Actions\ClassSession\ListSessions\ListClassSessionsAction;
use App\Http\Actions\ClassSession\Restore\RestoreClassSessionAction;
use App\Http\Actions\ClassSession\Update\UpdateClassSessionAction;
use App\Http\Actions\ClassSession\WeeklySchedule\GetWeeklyScheduleAction;
use App\Http\Actions\Booking\Create\CreateBookingAction;
use App\Http\Actions\Booking\Cancel\CancelBookingAction;
use App\Http\Actions\Booking\MemberBookings\GetMemberBookingsAction;
use App\Http\Actions\Booking\Roster\GetClassRosterAction;
use App\Http\Actions\Booking\AdminMemberBookings\GetAdminMemberBookingsAction;
use App\Http\Actions\Payments\Record\RecordPaymentAction;
use App\Http\Actions\Payments\List\ListPaymentsAction;
use App\Http\Actions\Payments\Detail\GetPaymentDetailAction;
use App\Http\Actions\Payments\Overdue\GetOverdueMembersAction;
use App\Http\Actions\MemberPayments\GetMyPaymentsAction;
use App\Http\Actions\Plans\ListMembershipPlansAction;
use App\Http\Actions\ClassTypes\ListClassTypesAction;
use App\Http\Actions\Staff\ListCoachesAction;
use Illuminate\Support\Facades\Route;

// Admin routes — members management
Route::prefix('admin')->middleware(['auth:sanctum', 'role.admin', 'force.password.change'])->group(function () {
    Route::get('/membership-plans', ListMembershipPlansAction::class);
    Route::get('/class-types',      ListClassTypesAction::class);
    Route::get('/coaches',          ListCoachesAction::class);
    Route::post('/members',                           CreateMemberAction::class);
    Route::get('/members',                            ListMembersAction::class);
    Route::get('/members/{id}',                       GetMemberDetailAction::class);
    Route::put('/members/{id}',                       UpdateMemberAction::class);
    Route::put('/members/{id}/plan',                  AssignMemberPlanAction::class);
    Route::put('/members/{id}/activate',              ActivateMemberAction::class);
    Route::put('/members/{id}/deactivate',            DeactivateMemberAction::class);
    Route::get('/class-sessions/{id}/roster',         GetClassRosterAction::class);
    Route::get('/members/{id}/bookings',              GetAdminMemberBookingsAction::class);

    // Payments — CRITICAL: /overdue must be before /{id}
    Route::post('/payments',          RecordPaymentAction::class);
    Route::get('/payments',           ListPaymentsAction::class);
    Route::get('/payments/overdue',   GetOverdueMembersAction::class);
    Route::get('/payments/{id}',      GetPaymentDetailAction::class);
});

// Member routes — self-service
Route::prefix('member')->middleware(['auth:sanctum', 'role.member', 'force.password.change'])->group(function () {
    Route::get('/profile', GetMemberProfileAction::class);
    Route::post('/bookings', CreateBookingAction::class);
    Route::patch('/bookings/{id}/cancel', CancelBookingAction::class);
    Route::get('/bookings', GetMemberBookingsAction::class);
    Route::get('/payments', GetMyPaymentsAction::class);
});

// Public — no auth required
Route::get('/schedule', GetWeeklyScheduleAction::class);

// Class sessions — admin management (auth:sanctum + admin role + force password change)
Route::middleware(['auth:sanctum', 'role.admin', 'force.password.change'])->group(function () {
    Route::prefix('class-sessions')->group(function () {
        Route::get('/',               ListClassSessionsAction::class);
        Route::post('/',              CreateClassSessionAction::class);
        Route::get('/{id}',           GetClassSessionAction::class);
        Route::put('/{id}',           UpdateClassSessionAction::class);
        Route::delete('/{id}',        DeleteClassSessionAction::class);
        Route::patch('/{id}/cancel',  CancelClassSessionAction::class);
        Route::patch('/{id}/restore', RestoreClassSessionAction::class);
    });
});

// Coach — auth:sanctum + coach role
Route::middleware(['auth:sanctum', 'role.coach'])->group(function () {
    Route::get('/coach/sessions', GetCoachSessionsAction::class);
    Route::get('/coach/class-sessions/{id}/roster', GetClassRosterAction::class);
});

Route::prefix('auth')->group(function () {
    Route::post('/login', LoginAction::class)->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', LogoutAction::class);

        Route::put('/password', ChangePasswordAction::class);

        // /me is accessible regardless of must_change_password — frontend uses it to decide redirection
        Route::get('/me', GetCurrentUserAction::class);
    });
});
