<?php

use App\Src\Core\ClassSession\Infrastructure\Tables\ClassSessionTable;
use App\Src\Core\ClassType\Infrastructure\Tables\ClassTypeTable;
use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(ClassSessionTable::TABLE_NAME, function (Blueprint $table) {
            $table->char(ClassSessionTable::ID, 26)->primary();
            $table->char(ClassSessionTable::CLASS_TYPE_ID, 26);
            $table->char(ClassSessionTable::COACH_ID, 26)->nullable()->default(null);
            $table->enum(ClassSessionTable::DAY_OF_WEEK, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']);
            $table->string(ClassSessionTable::TIME_SLOT, 5);
            $table->unsignedInteger(ClassSessionTable::MAX_CAPACITY)->default(20);
            $table->enum(ClassSessionTable::STATUS, ['active', 'cancelled'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            // Composite unique: allows Friday dual sessions (different class_type_id at same day+slot)
            $table->unique(
                [ClassSessionTable::DAY_OF_WEEK, ClassSessionTable::TIME_SLOT, ClassSessionTable::CLASS_TYPE_ID],
                'uq_day_slot_type'
            );

            // Performance indexes
            $table->index(ClassSessionTable::COACH_ID, 'idx_cs_coach_id');
            $table->index(ClassSessionTable::STATUS, 'idx_cs_status');
            $table->index(ClassSessionTable::DELETED_AT, 'idx_cs_deleted_at');

            // Foreign keys
            $table->foreign(ClassSessionTable::CLASS_TYPE_ID, 'fk_cs_class_type')
                ->references(ClassTypeTable::ID)
                ->on(ClassTypeTable::TABLE_NAME);

            $table->foreign(ClassSessionTable::COACH_ID, 'fk_cs_coach')
                ->references(UserTable::ID)
                ->on(UserTable::TABLE_NAME)
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(ClassSessionTable::TABLE_NAME);
    }
};
