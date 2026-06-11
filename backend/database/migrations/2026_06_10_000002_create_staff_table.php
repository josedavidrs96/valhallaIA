<?php

use App\Src\Core\Staff\Infrastructure\Tables\StaffTable;
use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(StaffTable::TABLE_NAME, function (Blueprint $table) {
            $table->char(StaffTable::ID, 26)->primary();
            $table->char(StaffTable::USER_ID, 26)->unique();
            $table->string(StaffTable::FIRST_NAME, 100);
            $table->string(StaffTable::LAST_NAME, 100);
            $table->string(StaffTable::PHONE, 20)->nullable();
            $table->string(StaffTable::SPECIALIZATION, 150)->nullable();
            $table->date(StaffTable::HIRE_DATE);
            $table->timestamps();

            $table->foreign(StaffTable::USER_ID)
                ->references(UserTable::ID)
                ->on(UserTable::TABLE_NAME)
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(StaffTable::TABLE_NAME);
    }
};
