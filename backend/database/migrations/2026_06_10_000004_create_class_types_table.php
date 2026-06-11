<?php

use App\Src\Core\ClassType\Infrastructure\Tables\ClassTypeTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(ClassTypeTable::TABLE_NAME, function (Blueprint $table) {
            $table->char(ClassTypeTable::ID, 26)->primary();
            $table->string(ClassTypeTable::NAME, 100);
            $table->string(ClassTypeTable::SLUG, 100)->unique();
            $table->text(ClassTypeTable::DESCRIPTION)->nullable();
            $table->string(ClassTypeTable::COLOR, 7)->nullable();
            $table->tinyInteger(ClassTypeTable::IS_ACTIVE)->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(ClassTypeTable::TABLE_NAME);
    }
};
