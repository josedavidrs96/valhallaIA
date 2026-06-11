<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

final class DefaultAdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_DEFAULT_PASSWORD');

        if (empty($password)) {
            throw new \RuntimeException('ADMIN_DEFAULT_PASSWORD env variable is required to seed the default admin.');
        }

        DB::table(UserTable::TABLE_NAME)->updateOrInsert(
            [UserTable::EMAIL => 'admin@valhallagym.com'],
            [
                UserTable::ID                   => (string) new Ulid(),
                UserTable::EMAIL                => 'admin@valhallagym.com',
                UserTable::PASSWORD             => password_hash($password, PASSWORD_BCRYPT),
                UserTable::ROLE                 => 'admin',
                UserTable::STATUS               => 'active',
                UserTable::MUST_CHANGE_PASSWORD => 1,
                UserTable::CREATED_AT           => now(),
                UserTable::UPDATED_AT           => now(),
            ],
        );
    }
}
