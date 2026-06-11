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

final class GetMemberBookingsTest extends TestCase
{
    use RefreshDatabase;

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

    private function createBooking(string $memberId, string $sessionId): string
    {
        $bookingId = (string) new Ulid();
        DB::table(BookingTable::TABLE_NAME)->insert([
            BookingTable::ID               => $bookingId,
            BookingTable::MEMBER_ID        => $memberId,
            BookingTable::CLASS_SESSION_ID => $sessionId,
            BookingTable::STATUS           => 'confirmed',
        ]);
        return $bookingId;
    }

    public function test_member_can_get_own_bookings(): void
    {
        $memberData = $this->createMemberUser();
        $sessionId  = $this->createSessionWithType();
        $this->createBooking($memberData['memberId'], $sessionId);

        $response = $this->actingAs($memberData['user'], 'sanctum')
            ->getJson('/api/member/bookings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'class_session_id', 'status',
                        'session' => ['day_of_week', 'time_slot', 'class_type_name', 'class_type_slug'],
                        'created_at',
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data');
    }

    public function test_member_with_no_bookings_returns_empty_array(): void
    {
        $memberData = $this->createMemberUser();

        $response = $this->actingAs($memberData['user'], 'sanctum')
            ->getJson('/api/member/bookings');

        $response->assertStatus(200)
            ->assertJsonPath('data', []);
    }
}
