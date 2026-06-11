<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use App\Src\Core\ClassSession\Infrastructure\Persistence\ClassSessionModel;
use App\Src\Core\ClassSession\Infrastructure\Tables\ClassSessionTable;
use App\Src\Core\ClassType\Infrastructure\Tables\ClassTypeTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class ClassSessionSeeder extends Seeder
{
    private const SLOTS = ['07:45', '12:15', '16:15', '17:30', '18:45', '20:00', '21:15'];

    public function run(): void
    {
        // Single query to fetch all class type IDs — no N+1
        $classTypes = DB::table(ClassTypeTable::TABLE_NAME)
            ->whereIn(ClassTypeTable::SLUG, [
                'tren-superior',
                'tren-inferior',
                'full-body',
                'gap',
                'entrenamiento-libre',
            ])
            ->pluck(ClassTypeTable::ID, ClassTypeTable::SLUG);

        $now     = now()->format('Y-m-d H:i:s');
        $records = [];

        // Monday + Wednesday: tren-superior × 7 slots = 14 sessions
        foreach (['monday', 'wednesday'] as $day) {
            foreach (self::SLOTS as $slot) {
                $records[] = $this->buildRecord($classTypes['tren-superior'], $day, $slot, $now);
            }
        }

        // Tuesday: tren-inferior × 7 slots = 7 sessions
        foreach (self::SLOTS as $slot) {
            $records[] = $this->buildRecord($classTypes['tren-inferior'], 'tuesday', $slot, $now);
        }

        // Thursday: full-body × 7 slots = 7 sessions
        foreach (self::SLOTS as $slot) {
            $records[] = $this->buildRecord($classTypes['full-body'], 'thursday', $slot, $now);
        }

        // Friday: gap × 7 + entrenamiento-libre × 7 = 14 sessions
        foreach (self::SLOTS as $slot) {
            $records[] = $this->buildRecord($classTypes['gap'], 'friday', $slot, $now);
            $records[] = $this->buildRecord($classTypes['entrenamiento-libre'], 'friday', $slot, $now);
        }

        // insertOrIgnore is idempotent — safe to run multiple times
        ClassSessionModel::query()->insertOrIgnore($records);
    }

    private function buildRecord(string $classTypeId, string $day, string $slot, string $now): array
    {
        return [
            ClassSessionTable::ID            => ClassSessionId::random()->value(),
            ClassSessionTable::CLASS_TYPE_ID => $classTypeId,
            ClassSessionTable::COACH_ID      => null,
            ClassSessionTable::DAY_OF_WEEK   => $day,
            ClassSessionTable::TIME_SLOT     => $slot,
            ClassSessionTable::MAX_CAPACITY  => 20,
            ClassSessionTable::STATUS        => 'active',
            ClassSessionTable::CREATED_AT    => $now,
            ClassSessionTable::UPDATED_AT    => $now,
        ];
    }
}
