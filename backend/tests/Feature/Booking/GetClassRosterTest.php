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

final class GetClassRosterTest extends TestCase
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

    private function createCoach(): UserModel
    {
        $data = [
            UserTable::ID                   => (string) new Ulid(),
            UserTable::EMAIL                => 'coach@valhallagym.com',
            UserTable::PASSWORD             => password_hash('Password123', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'coach',
            UserTable::STATUS               => 'active',
            UserTable::MUST_CHANGE_PASSWORD => 0,
        ];
        UserModel::query()->create($data);
        return UserModel::find($data[UserTable::ID]);
    }

    private function createMemberUser(string $email = 'member@gym.com'): array
    {
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
            MemberTable::MEMBER_NUMBER => 1,
            MemberTable::FIRST_NAME    => 'Carlos',
            MemberTable::LAST_NAME     => 'Ruiz',
            MemberTable::JOIN_DATE     => '2026-06-10',
        ]);

        return ['user' => UserModel::find($userId), 'memberId' => $memberId];
    }

    private function createClassSession(): string
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

    private function createBooking(string $memberId, string $sessionId): string
    {
        $bookingId = (string) new Ulid();
        DB::table(BookingTable::TABLE_NAME)->insert([
            BookingTable::ID               => $bookingId,
            BookingTable::MEMBER_ID        => $memberId,
            BookingTable::CLASS_SESSION_ID => $sessionId,
            BookingTable::SESSION_DATE     => '2099-06-16',
            BookingTable::STATUS           => 'confirmed',
        ]);
        return $bookingId;
    }

    public function test_admin_can_get_roster(): void
    {
        $admin      = $this->createAdmin();
        $memberData = $this->createMemberUser();
        $sessionId  = $this->createClassSession();
        $this->createBooking($memberData['memberId'], $sessionId);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/class-sessions/{$sessionId}/roster");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'capacity' => ['confirmed', 'available', 'max'],
                'roster'   => [
                    '*' => ['booking_id', 'member_id', 'member_number', 'first_name', 'last_name', 'status', 'booked_at'],
                ],
            ])
            ->assertJsonPath('capacity.confirmed', 1)
            ->assertJsonPath('capacity.max', 20);
    }

    public function test_coach_can_get_roster(): void
    {
        $coach     = $this->createCoach();
        $sessionId = $this->createClassSession();

        $response = $this->actingAs($coach, 'sanctum')
            ->getJson("/api/coach/class-sessions/{$sessionId}/roster");

        $response->assertStatus(200)
            ->assertJsonStructure(['capacity', 'roster']);
    }

    public function test_member_cannot_access_admin_roster(): void
    {
        $memberData = $this->createMemberUser();
        $sessionId  = $this->createClassSession();

        $response = $this->actingAs($memberData['user'], 'sanctum')
            ->getJson("/api/admin/class-sessions/{$sessionId}/roster");

        $response->assertStatus(403);
    }

    public function test_unknown_session_returns_404(): void
    {
        $admin   = $this->createAdmin();
        $fakeId  = (string) new Ulid();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/class-sessions/{$fakeId}/roster");

        $response->assertStatus(404);
    }
}
