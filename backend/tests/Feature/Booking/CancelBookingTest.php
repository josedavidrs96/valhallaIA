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

final class CancelBookingTest extends TestCase
{
    use RefreshDatabase;

    private function createMemberUser(string $email = 'member@gym.com', int $memberNumber = 1): array
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
            MemberTable::MEMBER_NUMBER => $memberNumber,
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

    private function createBooking(string $memberId, string $sessionId, string $status = 'confirmed'): string
    {
        $bookingId = (string) new Ulid();
        DB::table(BookingTable::TABLE_NAME)->insert([
            BookingTable::ID               => $bookingId,
            BookingTable::MEMBER_ID        => $memberId,
            BookingTable::CLASS_SESSION_ID => $sessionId,
            BookingTable::STATUS           => $status,
        ]);
        return $bookingId;
    }

    public function test_member_can_cancel_own_booking(): void
    {
        $memberData = $this->createMemberUser();
        $sessionId  = $this->createClassSession();
        $bookingId  = $this->createBooking($memberData['memberId'], $sessionId);

        $response = $this->actingAs($memberData['user'], 'sanctum')
            ->patchJson("/api/member/bookings/{$bookingId}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'cancelled');
    }

    public function test_unknown_booking_returns_404(): void
    {
        $memberData = $this->createMemberUser();
        $fakeId     = (string) new Ulid();

        $response = $this->actingAs($memberData['user'], 'sanctum')
            ->patchJson("/api/member/bookings/{$fakeId}/cancel");

        $response->assertStatus(404)
            ->assertJsonPath('code', 'BOOKING_NOT_FOUND');
    }

    public function test_other_member_cannot_cancel_booking_returns_403(): void
    {
        $member1Data = $this->createMemberUser('member1@gym.com', 1);
        $member2Data = $this->createMemberUser('member2@gym.com', 2);
        $sessionId   = $this->createClassSession();
        $bookingId   = $this->createBooking($member1Data['memberId'], $sessionId);

        $response = $this->actingAs($member2Data['user'], 'sanctum')
            ->patchJson("/api/member/bookings/{$bookingId}/cancel");

        $response->assertStatus(403)
            ->assertJsonPath('code', 'BOOKING_NOT_OWNED');
    }

    public function test_already_cancelled_booking_returns_422(): void
    {
        $memberData = $this->createMemberUser();
        $sessionId  = $this->createClassSession();
        $bookingId  = $this->createBooking($memberData['memberId'], $sessionId, 'cancelled');

        $response = $this->actingAs($memberData['user'], 'sanctum')
            ->patchJson("/api/member/bookings/{$bookingId}/cancel");

        $response->assertStatus(422)
            ->assertJsonPath('code', 'BOOKING_ALREADY_CANCELLED');
    }
}
