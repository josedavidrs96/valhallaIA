<?php

use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(UserTable::TABLE_NAME, function (Blueprint $table) {
            $table->char(UserTable::ID, 26)->primary();
            $table->string(UserTable::EMAIL, 191)->unique();
            $table->string(UserTable::PASSWORD, 255);
            $table->enum(UserTable::ROLE, ['admin', 'coach', 'member']);
            $table->enum(UserTable::STATUS, ['active', 'inactive', 'suspended', 'pending_approval'])->default('active');
            $table->tinyInteger(UserTable::MUST_CHANGE_PASSWORD)->default(0);
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists(UserTable::TABLE_NAME);
    }
};
