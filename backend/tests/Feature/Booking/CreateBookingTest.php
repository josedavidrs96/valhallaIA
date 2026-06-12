<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

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

final class CreateBookingTest extends TestCase
{
    use RefreshDatabase;

    private static int $memberCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();
        self::$memberCounter = 0;
    }

    private function createMemberWithPlan(string $email = 'member@gym.com', int $maxWeekly = 5): array
    {
        self::$memberCounter++;
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
            MemberTable::MEMBER_NUMBER => self::$memberCounter,
            MemberTable::FIRST_NAME    => 'Carlos',
            MemberTable::LAST_NAME     => 'Ruiz',
            MemberTable::JOIN_DATE     => '2026-06-10',
        ]);

        DB::table(MembershipPlanTable::TABLE_NAME)->insert([
            MembershipPlanTable::ID                 => $planId,
            MembershipPlanTable::NAME               => 'Plan Test',
            MembershipPlanTable::SLUG               => 'plan-test-' . self::$memberCounter,
            MembershipPlanTable::PRICE_CENTS        => 4000,
            MembershipPlanTable::CLASSES_PER_MONTH  => 25,
            MembershipPlanTable::MAX_WEEKLY_SESSIONS => $maxWeekly,
            MembershipPlanTable::IS_ACTIVE          => 1,
        ]);

        DB::table(MemberPlanAssignmentTable::TABLE_NAME)->insert([
            MemberPlanAssignmentTable::ID                 => (string) new Ulid(),
            MemberPlanAssignmentTable::MEMBER_ID          => $memberId,
            MemberPlanAssignmentTable::MEMBERSHIP_PLAN_ID => $planId,
            MemberPlanAssignmentTable::ASSIGNED_AT        => '2026-01-01',
        ]);

        return ['user' => UserModel::find($userId), 'memberId' => $memberId, 'planId' => $planId];
    }

    private function createClassType(): string
    {
        $id = (string) new Ulid();
        DB::table(ClassTypeTable::TABLE_NAME)->insert([
            ClassTypeTable::ID        => $id,
            ClassTypeTable::NAME      => 'Tren Superior',
            ClassTypeTable::SLUG      => 'tren-superior',
            ClassTypeTable::COLOR     => '#2563eb',
            ClassTypeTable::IS_ACTIVE => 1,
        ]);
        return $id;
    }

    private function createClassSession(string $classTypeId, int $capacity = 20, string $status = 'active'): string
    {
        $id = (string) new Ulid();
        DB::table(ClassSessionTable::TABLE_NAME)->insert([
            ClassSessionTable::ID            => $id,
            ClassSessionTable::CLASS_TYPE_ID => $classTypeId,
            ClassSessionTable::DAY_OF_WEEK   => 'monday',
            ClassSessionTable::TIME_SLOT     => '20:00',
            ClassSessionTable::MAX_CAPACITY  => $capacity,
            ClassSessionTable::STATUS        => $status,
        ]);
        return $id;
    }

    public function test_member_can_create_booking(): void
    {
        $memberData  = $this->createMemberWithPlan();
        $classTypeId = $this->createClassType();
        $sessionId   = $this->createClassSession($classTypeId);

        $response = $this->actingAs($memberData['user'], 'sanctum')
            ->postJson('/api/member/bookings', ['class_session_id' => $sessionId]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id', 'member_id', 'class_session_id', 'session_date', 'status',
                'session' => ['day_of_week', 'time_slot', 'class_type_name', 'class_type_slug'],
                'created_at',
            ])
            ->assertJsonPath('status', 'confirmed')
            ->assertJsonPath('class_session_id', $sessionId);

        $this->assertNotNull($response->json('session_date'));
    }

    public function test_unauthenticated_returns_401(): void
    {
        $classTypeId = $this->createClassType();
        $sessionId   = $this->createClassSession($classTypeId);

        $this->postJson('/api/member/bookings', ['class_session_id' => $sessionId])
            ->assertStatus(401);
    }

    public function test_booking_same_session_twice_returns_409(): void
    {
        $memberData  = $this->createMemberWithPlan();
        $classTypeId = $this->createClassType();
        $sessionId   = $this->createClassSession($classTypeId);

        $this->actingAs($memberData['user'], 'sanctum')
            ->postJson('/api/member/bookings', ['class_session_id' => $sessionId]);

        $this->actingAs($memberData['user'], 'sanctum')
            ->postJson('/api/member/bookings', ['class_session_id' => $sessionId])
            ->assertStatus(409)
            ->assertJsonPath('code', 'BOOKING_ALREADY_EXISTS');
    }

    public function test_session_full_returns_422(): void
    {
        $member1Data = $this->createMemberWithPlan('member1@gym.com');
        $member2Data = $this->createMemberWithPlan('member2@gym.com');
        $classTypeId = $this->createClassType();
        $sessionId   = $this->createClassSession($classTypeId, 1);

        $this->actingAs($member1Data['user'], 'sanctum')
            ->postJson('/api/member/bookings', ['class_session_id' => $sessionId])
            ->assertStatus(201);

        $this->actingAs($member2Data['user'], 'sanctum')
            ->postJson('/api/member/bookings', ['class_session_id' => $sessionId])
            ->assertStatus(422)
            ->assertJsonPath('code', 'SESSION_FULL');
    }

    public function test_cancelled_session_returns_422(): void
    {
        $memberData  = $this->createMemberWithPlan();
        $classTypeId = $this->createClassType();
        $sessionId   = $this->createClassSession($classTypeId, 20, 'cancelled');

        $this->actingAs($memberData['user'], 'sanctum')
            ->postJson('/api/member/bookings', ['class_session_id' => $sessionId])
            ->assertStatus(422)
            ->assertJsonPath('code', 'SESSION_NOT_AVAILABLE');
    }

    public function test_member_without_plan_returns_422(): void
    {
        // Create member without plan assignment
        $userId   = (string) new Ulid();
        $memberId = (string) new Ulid();

        UserModel::query()->create([
            UserTable::ID                   => $userId,
            UserTable::EMAIL                => 'noplan@gym.com',
            UserTable::PASSWORD             => password_hash('Password123', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'member',
            UserTable::STATUS               => 'active',
            UserTable::MUST_CHANGE_PASSWORD => 0,
        ]);

        DB::table(MemberTable::TABLE_NAME)->insert([
            MemberTable::ID            => $memberId,
            MemberTable::USER_ID       => $userId,
            MemberTable::MEMBER_NUMBER => 99,
            MemberTable::FIRST_NAME    => 'Sin',
            MemberTable::LAST_NAME     => 'Plan',
            MemberTable::JOIN_DATE     => '2026-06-10',
        ]);

        $user        = UserModel::find($userId);
        $classTypeId = $this->createClassType();
        $sessionId   = $this->createClassSession($classTypeId);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/member/bookings', ['class_session_id' => $sessionId])
            ->assertStatus(422)
            ->assertJsonPath('code', 'MEMBER_HAS_NO_PLAN');
    }

    public function test_weekly_limit_reached_returns_422(): void
    {
        // Plan with weekly limit of 1
        $memberData  = $this->createMemberWithPlan('limited@gym.com', 1);
        $classTypeId = $this->createClassType();

        // First session (different slot to avoid duplicate check)
        $session1Id = (string) new Ulid();
        DB::table(ClassSessionTable::TABLE_NAME)->insert([
            ClassSessionTable::ID            => $session1Id,
            ClassSessionTable::CLASS_TYPE_ID => $classTypeId,
            ClassSessionTable::DAY_OF_WEEK   => 'monday',
            ClassSessionTable::TIME_SLOT     => '07:45',
            ClassSessionTable::MAX_CAPACITY  => 20,
            ClassSessionTable::STATUS        => 'active',
        ]);

        $session2Id = (string) new Ulid();
        DB::table(ClassSessionTable::TABLE_NAME)->insert([
            ClassSessionTable::ID            => $session2Id,
            ClassSessionTable::CLASS_TYPE_ID => $classTypeId,
            ClassSessionTable::DAY_OF_WEEK   => 'tuesday',
            ClassSessionTable::TIME_SLOT     => '07:45',
            ClassSessionTable::MAX_CAPACITY  => 20,
            ClassSessionTable::STATUS        => 'active',
        ]);

        // First booking succeeds
        $this->actingAs($memberData['user'], 'sanctum')
            ->postJson('/api/member/bookings', ['class_session_id' => $session1Id])
            ->assertStatus(201);

        // Second booking this week hits the limit
        $this->actingAs($memberData['user'], 'sanctum')
            ->postJson('/api/member/bookings', ['class_session_id' => $session2Id])
            ->assertStatus(422)
            ->assertJsonPath('code', 'WEEKLY_LIMIT_REACHED');
    }
}
