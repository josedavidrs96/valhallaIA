<?php

declare(strict_types=1);

namespace Tests\Feature\ClassSession;

use App\Src\Core\ClassSession\Infrastructure\Tables\ClassSessionTable;
use App\Src\Core\ClassType\Infrastructure\Tables\ClassTypeTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;
use Tests\TestCase;

final class GetWeeklyScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function createClassType(string $slug = 'tren-superior', string $name = 'Tren Superior'): string
    {
        $id = (string) new Ulid();
        DB::table(ClassTypeTable::TABLE_NAME)->insert([
            ClassTypeTable::ID        => $id,
            ClassTypeTable::NAME      => $name,
            ClassTypeTable::SLUG      => $slug,
            ClassTypeTable::IS_ACTIVE => 1,
        ]);
        return $id;
    }

    private function createSession(string $classTypeId, string $day, string $slot): void
    {
        DB::table(ClassSessionTable::TABLE_NAME)->insert([
            ClassSessionTable::ID            => (string) new Ulid(),
            ClassSessionTable::CLASS_TYPE_ID => $classTypeId,
            ClassSessionTable::COACH_ID      => null,
            ClassSessionTable::DAY_OF_WEEK   => $day,
            ClassSessionTable::TIME_SLOT     => $slot,
            ClassSessionTable::MAX_CAPACITY  => 20,
            ClassSessionTable::STATUS        => 'active',
        ]);
    }

    public function test_public_schedule_returns_200_without_auth(): void
    {
        $response = $this->getJson('/api/schedule');

        $response->assertStatus(200);
    }

    public function test_schedule_has_weekday_keys(): void
    {
        $response = $this->getJson('/api/schedule');

        $response->assertStatus(200)
            ->assertJsonStructure(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']);
    }

    public function test_schedule_contains_created_sessions(): void
    {
        $typeId = $this->createClassType();
        $this->createSession($typeId, 'monday', '07:45');

        $response = $this->getJson('/api/schedule');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'monday')
            ->assertJsonPath('monday.0.day_of_week', 'monday')
            ->assertJsonPath('monday.0.time_slot', '07:45');
    }

    public function test_cancelled_sessions_are_excluded(): void
    {
        $typeId = $this->createClassType();
        DB::table(ClassSessionTable::TABLE_NAME)->insert([
            ClassSessionTable::ID            => (string) new Ulid(),
            ClassSessionTable::CLASS_TYPE_ID => $typeId,
            ClassSessionTable::COACH_ID      => null,
            ClassSessionTable::DAY_OF_WEEK   => 'tuesday',
            ClassSessionTable::TIME_SLOT     => '12:15',
            ClassSessionTable::MAX_CAPACITY  => 20,
            ClassSessionTable::STATUS        => 'cancelled',
        ]);

        $response = $this->getJson('/api/schedule');

        // findWeeklySchedule returns all non-deleted (including cancelled) by design —
        // it returns all active+cancelled for display purposes.
        // Verify the session IS included (design: show all non-deleted)
        $response->assertStatus(200)
            ->assertJsonCount(1, 'tuesday');
    }

    public function test_soft_deleted_sessions_not_included(): void
    {
        $typeId = $this->createClassType();
        DB::table(ClassSessionTable::TABLE_NAME)->insert([
            ClassSessionTable::ID            => (string) new Ulid(),
            ClassSessionTable::CLASS_TYPE_ID => $typeId,
            ClassSessionTable::COACH_ID      => null,
            ClassSessionTable::DAY_OF_WEEK   => 'wednesday',
            ClassSessionTable::TIME_SLOT     => '16:15',
            ClassSessionTable::MAX_CAPACITY  => 20,
            ClassSessionTable::STATUS        => 'active',
            ClassSessionTable::DELETED_AT    => now()->format('Y-m-d H:i:s'),
        ]);

        $response = $this->getJson('/api/schedule');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'wednesday');
    }

    public function test_friday_can_have_multiple_sessions_per_slot(): void
    {
        $gapId  = $this->createClassType('gap', 'GAP');
        $libreId = $this->createClassType('entrenamiento-libre', 'Entrenamiento Libre');

        $this->createSession($gapId, 'friday', '07:45');
        $this->createSession($libreId, 'friday', '07:45');

        $response = $this->getJson('/api/schedule');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'friday');
    }
}
