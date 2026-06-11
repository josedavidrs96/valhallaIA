<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use App\Src\Core\Booking\Infrastructure\Tables\BookingTable;
use App\Src\Core\ClassSession\Infrastructure\Tables\ClassSessionTable;
use App\Src\Core\ClassType\Infrastructure\Tables\ClassTypeTable;
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

    private function createAdmin(): UserModel
    {
        $data = [
            UserTable::ID                   => (string) new Ulid(),
            UserTable::EMAIL                => 'admin@valhallagym.com',
            UserTable::PASSWORD             => password_hash('Password123', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'admin',
            UserTable::STATUS               => 'active',
            UserTable::MUST_CHANGE_PASSWORD => 0,
        ];
        UserModel::query()->create($data);
        return UserModel::find($data[UserTable::ID]);
    }

    private static int $memberCounter = 0;

    private function createMemberUser(string $email = 'member@gym.com'): array
    {
        self::$memberCounter++;
        $userId   = (string) new Ulid();
        $memberId = (string) new Ulid();

        $user = UserModel::query()->create([
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

        return ['user' => UserModel::find($userId), 'memberId' => $memberId];
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::$memberCounter = 0;
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
            ClassSessionTable::TIME_SLOT     => '07:45',
            ClassSessionTable::MAX_CAPACITY  => $capacity,
            ClassSessionTable::STATUS        => $status,
        ]);
        return $id;
    }

    public function test_member_can_create_booking(): void
    {
        $memberData    = $this->createMemberUser();
        $classTypeId   = $this->createClassType();
        $sessionId     = $this->createClassSession($classTypeId);

        $response = $this->actingAs($memberData['user'], 'sanctum')
            ->postJson('/api/member/bookings', ['class_session_id' => $sessionId]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id', 'member_id', 'class_session_id', 'status',
                'session' => ['day_of_week', 'time_slot', 'class_type_name', 'class_type_slug'],
                'created_at',
            ])
            ->assertJsonPath('status', 'confirmed')
            ->assertJsonPath('class_session_id', $sessionId);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $classTypeId = $this->createClassType();
        $sessionId   = $this->createClassSession($classTypeId);

        $response = $this->postJson('/api/member/bookings', ['class_session_id' => $sessionId]);
        $response->assertStatus(401);
    }

    public function test_booking_same_session_twice_returns_409(): void
    {
        $memberData  = $this->createMemberUser();
        $classTypeId = $this->createClassType();
        $sessionId   = $this->createClassSession($classTypeId);

        $this->actingAs($memberData['user'], 'sanctum')
            ->postJson('/api/member/bookings', ['class_session_id' => $sessionId]);

        $response = $this->actingAs($memberData['user'], 'sanctum')
            ->postJson('/api/member/bookings', ['class_session_id' => $sessionId]);

        $response->assertStatus(409)
            ->assertJsonPath('code', 'BOOKING_ALREADY_EXISTS');
    }

    public function test_session_full_returns_422(): void
    {
        $member1Data = $this->createMemberUser('member1@gym.com');
        $member2Data = $this->createMemberUser('member2@gym.com');

        $classTypeId = $this->createClassType();
        $sessionId   = $this->createClassSession($classTypeId, 1); // capacity = 1

        // First booking — should succeed
        $this->actingAs($member1Data['user'], 'sanctum')
            ->postJson('/api/member/bookings', ['class_session_id' => $sessionId])
            ->assertStatus(201);

        // Second booking — session full
        $response = $this->actingAs($member2Data['user'], 'sanctum')
            ->postJson('/api/member/bookings', ['class_session_id' => $sessionId]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'SESSION_FULL');
    }

    public function test_cancelled_session_returns_422(): void
    {
        $memberData  = $this->createMemberUser();
        $classTypeId = $this->createClassType();
        $sessionId   = $this->createClassSession($classTypeId, 20, 'cancelled');

        $response = $this->actingAs($memberData['user'], 'sanctum')
            ->postJson('/api/member/bookings', ['class_session_id' => $sessionId]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'SESSION_NOT_AVAILABLE');
    }
}
