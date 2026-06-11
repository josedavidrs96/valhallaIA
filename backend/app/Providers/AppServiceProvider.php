<?php

declare(strict_types=1);

namespace App\Providers;

use App\Src\Core\Member\Application\Commands\ActivateMember\ActivateMemberHandler;
use App\Src\Core\Member\Application\Commands\AssignMembershipPlan\AssignMembershipPlanHandler;
use App\Src\Core\Member\Application\Commands\CreateMember\CreateMemberHandler;
use App\Src\Core\Member\Application\Commands\DeactivateMember\DeactivateMemberHandler;
use App\Src\Core\Member\Application\Commands\UpdateMember\UpdateMemberHandler;
use App\Src\Core\Member\Application\Queries\GetMemberById\GetMemberByIdHandler;
use App\Src\Core\Member\Application\Queries\GetMemberProfile\GetMemberProfileHandler;
use App\Src\Core\Member\Application\Queries\ListMembers\ListMembersHandler;
use App\Src\Core\Member\Domain\Repositories\MemberRepositoryInterface;
use App\Src\Core\Member\Domain\Repositories\MembershipPlanRepositoryInterface;
use App\Src\Core\Member\Infrastructure\Hydrators\MemberHydrator;
use App\Src\Core\Member\Infrastructure\Hydrators\MembershipPlanHydrator;
use App\Src\Core\Member\Infrastructure\Repositories\MemberRepository;
use App\Src\Core\Member\Infrastructure\Repositories\MembershipPlanRepository;
use App\Src\Shared\Auth\Application\Commands\ChangePassword\ChangePasswordHandler;
use App\Src\Shared\Auth\Application\Commands\Logout\LogoutHandler;
use App\Src\Shared\Auth\Application\Queries\Authenticate\AuthenticateHandler;
use App\Src\Shared\Auth\Application\Queries\GetAuthenticatedUser\GetAuthenticatedUserHandler;
use App\Src\Shared\Auth\Domain\Repositories\UserRepositoryInterface;
use App\Src\Shared\Auth\Infrastructure\Hydrators\UserHydrator;
use App\Src\Core\ClassSession\Application\Commands\CancelClassSession\CancelClassSessionHandler;
use App\Src\Core\ClassSession\Application\Commands\CreateClassSession\CreateClassSessionHandler;
use App\Src\Core\ClassSession\Application\Commands\DeleteClassSession\DeleteClassSessionHandler;
use App\Src\Core\ClassSession\Application\Commands\RestoreClassSession\RestoreClassSessionHandler;
use App\Src\Core\ClassSession\Application\Commands\UpdateClassSession\UpdateClassSessionHandler;
use App\Src\Core\ClassSession\Application\Queries\GetClassSessionById\GetClassSessionByIdHandler;
use App\Src\Core\ClassSession\Application\Queries\GetCoachSessions\GetCoachSessionsHandler;
use App\Src\Core\ClassSession\Application\Queries\GetWeeklySchedule\GetWeeklyScheduleHandler;
use App\Src\Core\ClassSession\Application\Queries\ListClassSessions\ListClassSessionsHandler;
use App\Src\Core\ClassSession\Domain\Repositories\ClassSessionRepositoryInterface;
use App\Src\Core\ClassSession\Infrastructure\Hydrators\ClassSessionHydrator;
use App\Src\Core\ClassSession\Infrastructure\Repositories\ClassSessionRepository;
use App\Src\Core\Booking\Application\Commands\CreateBooking\CreateBookingHandler;
use App\Src\Core\Booking\Application\Commands\CancelBooking\CancelBookingHandler;
use App\Src\Core\Booking\Application\Queries\GetBookingById\GetBookingByIdHandler;
use App\Src\Core\Booking\Application\Queries\GetMemberBookings\GetMemberBookingsHandler;
use App\Src\Core\Booking\Application\Queries\GetClassRoster\GetClassRosterHandler;
use App\Src\Core\Booking\Domain\Repositories\BookingRepositoryInterface;
use App\Src\Core\Booking\Infrastructure\Hydrators\BookingHydrator;
use App\Src\Core\Booking\Infrastructure\Repositories\BookingRepository;
use App\Src\Shared\Auth\Infrastructure\Repositories\UserRepository;
use App\Src\Billing\Payment\Domain\Repositories\PaymentRepositoryInterface;
use App\Src\Billing\Payment\Infrastructure\Hydrators\PaymentHydrator;
use App\Src\Billing\Payment\Infrastructure\Repositories\PaymentRepository;
use App\Src\Billing\Payment\Application\Commands\RecordPayment\RecordPaymentHandler;
use App\Src\Billing\Payment\Application\Queries\GetPaymentById\GetPaymentByIdHandler;
use App\Src\Billing\Payment\Application\Queries\ListPayments\ListPaymentsHandler;
use App\Src\Billing\Payment\Application\Queries\GetOverdueMembers\GetOverdueMembersHandler;
use App\Src\Billing\Payment\Application\Queries\GetMyPayments\GetMyPaymentsHandler;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        $this->app->bind(UserRepository::class, fn() => new UserRepository(new UserHydrator()));

        $this->app->bind(AuthenticateHandler::class, fn($app) => new AuthenticateHandler(
            $app->make(UserRepositoryInterface::class)
        ));

        $this->app->bind(LogoutHandler::class, fn() => new LogoutHandler());

        $this->app->bind(ChangePasswordHandler::class, fn($app) => new ChangePasswordHandler(
            $app->make(UserRepositoryInterface::class)
        ));

        $this->app->bind(GetAuthenticatedUserHandler::class, fn($app) => new GetAuthenticatedUserHandler(
            $app->make(UserRepositoryInterface::class)
        ));

        // Core/Member bindings
        $this->app->bind(MemberRepositoryInterface::class, MemberRepository::class);
        $this->app->bind(MembershipPlanRepositoryInterface::class, MembershipPlanRepository::class);

        $this->app->bind(MemberRepository::class, fn() => new MemberRepository(new MemberHydrator()));
        $this->app->bind(MembershipPlanRepository::class, fn() => new MembershipPlanRepository(new MembershipPlanHydrator()));

        $this->app->bind(CreateMemberHandler::class, fn($app) => new CreateMemberHandler(
            $app->make(UserRepositoryInterface::class),
            $app->make(MemberRepositoryInterface::class),
            $app->make(MembershipPlanRepositoryInterface::class),
        ));

        $this->app->bind(UpdateMemberHandler::class, fn($app) => new UpdateMemberHandler(
            $app->make(MemberRepositoryInterface::class),
        ));

        $this->app->bind(AssignMembershipPlanHandler::class, fn($app) => new AssignMembershipPlanHandler(
            $app->make(MemberRepositoryInterface::class),
            $app->make(MembershipPlanRepositoryInterface::class),
        ));

        $this->app->bind(ActivateMemberHandler::class, fn($app) => new ActivateMemberHandler(
            $app->make(MemberRepositoryInterface::class),
            $app->make(UserRepositoryInterface::class),
        ));

        $this->app->bind(DeactivateMemberHandler::class, fn($app) => new DeactivateMemberHandler(
            $app->make(MemberRepositoryInterface::class),
            $app->make(UserRepositoryInterface::class),
        ));

        $this->app->bind(GetMemberByIdHandler::class, fn($app) => new GetMemberByIdHandler(
            $app->make(MemberRepositoryInterface::class),
        ));

        $this->app->bind(ListMembersHandler::class, fn($app) => new ListMembersHandler(
            $app->make(MemberRepositoryInterface::class),
        ));

        $this->app->bind(GetMemberProfileHandler::class, fn($app) => new GetMemberProfileHandler(
            $app->make(MemberRepositoryInterface::class),
        ));

        // --- Core/ClassSession bindings ---

        $this->app->bind(ClassSessionRepositoryInterface::class, ClassSessionRepository::class);

        $this->app->bind(ClassSessionRepository::class, fn() => new ClassSessionRepository(
            new ClassSessionHydrator()
        ));

        $this->app->bind(CreateClassSessionHandler::class, fn($app) => new CreateClassSessionHandler(
            $app->make(ClassSessionRepositoryInterface::class),
            $app->make(UserRepositoryInterface::class),
        ));

        $this->app->bind(UpdateClassSessionHandler::class, fn($app) => new UpdateClassSessionHandler(
            $app->make(ClassSessionRepositoryInterface::class),
            $app->make(UserRepositoryInterface::class),
        ));

        $this->app->bind(CancelClassSessionHandler::class, fn($app) => new CancelClassSessionHandler(
            $app->make(ClassSessionRepositoryInterface::class),
        ));

        $this->app->bind(RestoreClassSessionHandler::class, fn($app) => new RestoreClassSessionHandler(
            $app->make(ClassSessionRepositoryInterface::class),
        ));

        $this->app->bind(DeleteClassSessionHandler::class, fn($app) => new DeleteClassSessionHandler(
            $app->make(ClassSessionRepositoryInterface::class),
        ));

        $this->app->bind(GetClassSessionByIdHandler::class, fn($app) => new GetClassSessionByIdHandler(
            $app->make(ClassSessionRepositoryInterface::class),
        ));

        $this->app->bind(ListClassSessionsHandler::class, fn($app) => new ListClassSessionsHandler(
            $app->make(ClassSessionRepositoryInterface::class),
        ));

        $this->app->bind(GetWeeklyScheduleHandler::class, fn($app) => new GetWeeklyScheduleHandler(
            $app->make(ClassSessionRepositoryInterface::class),
        ));

        $this->app->bind(GetCoachSessionsHandler::class, fn($app) => new GetCoachSessionsHandler(
            $app->make(ClassSessionRepositoryInterface::class),
        ));

        // --- Core/Booking bindings ---

        $this->app->bind(BookingRepositoryInterface::class, BookingRepository::class);

        $this->app->bind(BookingRepository::class, fn() => new BookingRepository(new BookingHydrator()));

        $this->app->bind(CreateBookingHandler::class, fn($app) => new CreateBookingHandler(
            $app->make(BookingRepositoryInterface::class),
            $app->make(ClassSessionRepositoryInterface::class),
        ));

        $this->app->bind(CancelBookingHandler::class, fn($app) => new CancelBookingHandler(
            $app->make(BookingRepositoryInterface::class),
        ));

        $this->app->bind(GetBookingByIdHandler::class, fn($app) => new GetBookingByIdHandler(
            $app->make(BookingRepositoryInterface::class),
        ));

        $this->app->bind(GetMemberBookingsHandler::class, fn($app) => new GetMemberBookingsHandler(
            $app->make(BookingRepositoryInterface::class),
        ));

        $this->app->bind(GetClassRosterHandler::class, fn($app) => new GetClassRosterHandler(
            $app->make(BookingRepositoryInterface::class),
            $app->make(ClassSessionRepositoryInterface::class),
        ));

        // --- Billing/Payment bindings ---
        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);
        $this->app->bind(PaymentRepository::class, fn() => new PaymentRepository(new PaymentHydrator()));

        $this->app->bind(RecordPaymentHandler::class, fn($app) => new RecordPaymentHandler(
            $app->make(PaymentRepositoryInterface::class),
            $app->make(MemberRepositoryInterface::class),
            $app->make(MembershipPlanRepositoryInterface::class),
        ));

        $this->app->bind(GetPaymentByIdHandler::class, fn($app) => new GetPaymentByIdHandler(
            $app->make(PaymentRepositoryInterface::class),
        ));

        $this->app->bind(ListPaymentsHandler::class, fn($app) => new ListPaymentsHandler(
            $app->make(PaymentRepositoryInterface::class),
        ));

        $this->app->bind(GetOverdueMembersHandler::class, fn($app) => new GetOverdueMembersHandler(
            $app->make(PaymentRepositoryInterface::class),
        ));

        $this->app->bind(GetMyPaymentsHandler::class, fn($app) => new GetMyPaymentsHandler(
            $app->make(PaymentRepositoryInterface::class),
        ));
    }

    public function boot(): void {}
}
