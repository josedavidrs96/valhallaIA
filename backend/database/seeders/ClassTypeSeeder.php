<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Src\Core\ClassType\Infrastructure\Tables\ClassTypeTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

final class ClassTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                ClassTypeTable::SLUG        => 'tren-superior',
                ClassTypeTable::NAME        => 'Tren Superior',
                ClassTypeTable::DESCRIPTION => 'Entrenamiento enfocado en la parte superior del cuerpo.',
                ClassTypeTable::COLOR       => '#2563eb',
            ],
            [
                ClassTypeTable::SLUG        => 'tren-inferior',
                ClassTypeTable::NAME        => 'Tren Inferior',
                ClassTypeTable::DESCRIPTION => 'Entrenamiento enfocado en la parte inferior del cuerpo.',
                ClassTypeTable::COLOR       => '#7c3aed',
            ],
            [
                ClassTypeTable::SLUG        => 'full-body',
                ClassTypeTable::NAME        => 'Full Body',
                ClassTypeTable::DESCRIPTION => 'Entrenamiento de cuerpo completo.',
                ClassTypeTable::COLOR       => '#059669',
            ],
            [
                ClassTypeTable::SLUG        => 'gap',
                ClassTypeTable::NAME        => 'GAP',
                ClassTypeTable::DESCRIPTION => 'Gluteos, abdominales y piernas.',
                ClassTypeTable::COLOR       => '#d97706',
            ],
            [
                ClassTypeTable::SLUG        => 'entrenamiento-libre',
                ClassTypeTable::NAME        => 'Entrenamiento Libre',
                ClassTypeTable::DESCRIPTION => 'Sesion de entrenamiento libre sin estructura fija.',
                ClassTypeTable::COLOR       => '#6b7280',
            ],
        ];

        foreach ($types as $type) {
            DB::table(ClassTypeTable::TABLE_NAME)->insertOrIgnore(
                array_merge($type, [
                    ClassTypeTable::ID         => (string) new Ulid(),
                    ClassTypeTable::IS_ACTIVE  => 1,
                    ClassTypeTable::CREATED_AT => now(),
                    ClassTypeTable::UPDATED_AT => now(),
                ]),
            );
        }
    }
}
