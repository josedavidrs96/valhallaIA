<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use App\Src\Core\Booking\Infrastructure\Tables\BookingTable;
use App\Src\Core\ClassSession\Infrastructure\Tables\ClassSessionTable;
use App\Src\Core\ClassType\Infrastructure\Tables\ClassTypeTable;
use App\Src\Core\Member\Infrastructure\Tables\MemberPlanAssignmentTable;
use App\Src\Core\Member\Infrastructure\Tables\MembershipPlanTable;
use App\Src\Core\Member\Infrastructure\Tables\MemberTable;
use App\Src\Shared\Auth\Infrastructure\Persistence\UserModel;
use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;
use Tests\TestCase;

final class GetMemberBookingsTest extends TestCase
{
    use RefreshDatabase;

    private function createMemberWithPlan(string $email = 'member@gym.com'): array
    {
        $userId   = (string) new Ulid();
        $memberId = (string) new Ulid();
        $planId   = (string) new Ulid();

        UserModel::query()->create([
            UserTable::ID                   => $userId,
            UserTable::EMAIL                => $email,
            UserTable::PASSWORD             => password_hash('Password123', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'member',
            UserTable::STATUS               => 'active',
            UserTable::MUST_CHANGE_PASSWORD => 0,
        ]);

        DB::table(MemberTable::TABLE_NAME)->insert([
            MemberTable::ID            => $memberId,
            MemberTable::USER_ID       => $userId,
            MemberTable::MEMBER_NUMBER => 1,
            MemberTable::FIRST_NAME    => 'Carlos',
            MemberTable::LAST_NAME     => 'Ruiz',
            MemberTable::JOIN_DATE     => '2026-06-10',
        ]);

        DB::table(MembershipPlanTable::TABLE_NAME)->insert([
            MembershipPlanTable::ID                 => $planId,
            MembershipPlanTable::NAME               => 'Plan Test',
            MembershipPlanTable::SLUG               => 'plan-test',
            MembershipPlanTable::PRICE_CENTS        => 4000,
            MembershipPlanTable::CLASSES_PER_MONTH  => 25,
            MembershipPlanTable::MAX_WEEKLY_SESSIONS => 5,
            MembershipPlanTable::IS_ACTIVE          => 1,
        ]);

        DB::table(MemberPlanAssignmentTable::TABLE_NAME)->insert([
            MemberPlanAssignmentTable::ID                 => (string) new Ulid(),
            MemberPlanAssignmentTable::MEMBER_ID          => $memberId,
            MemberPlanAssignmentTable::MEMBERSHIP_PLAN_ID => $planId,
            MemberPlanAssignmentTable::ASSIGNED_AT        => '2026-01-01',
        ]);

        return ['user' => UserModel::find($userId), 'memberId' => $memberId];
    }

    private function createSessionWithType(): string
    {
        $classTypeId = (string) new Ulid();
        DB::table(ClassTypeTable::TABLE_NAME)->insert([
            ClassTypeTable::ID        => $classTypeId,
            ClassTypeTable::NAME      => 'Tren Superior',
            ClassTypeTable::SLUG      => 'tren-superior',
            ClassTypeTable::COLOR     => '#2563eb',
            ClassTypeTable::IS_ACTIVE => 1,
        ]);

        $sessionId = (string) new Ulid();
        DB::table(ClassSessionTable::TABLE_NAME)->insert([
            ClassSessionTable::ID            => $sessionId,
            ClassSessionTable::CLASS_TYPE_ID => $classTypeId,
            ClassSessionTable::DAY_OF_WEEK   => 'monday',
            ClassSessionTable::TIME_SLOT     => '07:45',
            ClassSessionTable::MAX_CAPACITY  => 20,
            ClassSessionTable::STATUS        => 'active',
        ]);

        return $sessionId;
    }

    private function createBooking(string $memberId, string $sessionId, string $sessionDate = '2099-06-16'): string
    {
        $bookingId = (string) new Ulid();
        DB::table(BookingTable::TABLE_NAME)->insert([
            BookingTable::ID               => $bookingId,
            BookingTable::MEMBER_ID        => $memberId,
            BookingTable::CLASS_SESSION_ID => $sessionId,
            BookingTable::SESSION_DATE     => $sessionDate,
            BookingTable::STATUS           => 'confirmed',
        ]);
        return $bookingId;
    }

    public function test_member_can_get_own_bookings(): void
    {
        $memberData = $this->createMemberWithPlan();
        $sessionId  = $this->createSessionWithType();
        $this->createBooking($memberData['memberId'], $sessionId);

        $response = $this->actingAs($memberData['user'], 'sanctum')
            ->getJson('/api/member/bookings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'class_session_id', 'session_date', 'status',
                        'session' => ['day_of_week', 'time_slot', 'class_type_name', 'class_type_slug'],
                        'created_at',
                    ],
                ],
                'weekly_used',
                'weekly_max',
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('weekly_max', 5);
    }

    public function test_member_with_no_bookings_returns_empty_array(): void
    {
        $memberData = $this->createMemberWithPlan();

        $response = $this->actingAs($memberData['user'], 'sanctum')
            ->getJson('/api/member/bookings');

        $response->assertStatus(200)
            ->assertJsonPath('data', [])
            ->assertJsonPath('weekly_used', 0);
    }
}
