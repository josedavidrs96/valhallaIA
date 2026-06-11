<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MembershipPlanSeeder::class,
            ClassTypeSeeder::class,
            ClassSessionSeeder::class,  // Must run after ClassTypeSeeder (FK dependency)
            DefaultAdminSeeder::class,
        ]);
    }
}
