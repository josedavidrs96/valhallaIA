<?php

declare(strict_types=1);

namespace App\Http\Actions\ClassTypes;

use App\Src\Core\ClassType\Infrastructure\Tables\ClassTypeTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class ListClassTypesAction
{
    public function __invoke(): JsonResponse
    {
        $rows = DB::table(ClassTypeTable::TABLE_NAME)
            ->where(ClassTypeTable::IS_ACTIVE, 1)
            ->orderBy(ClassTypeTable::NAME)
            ->get([
                ClassTypeTable::ID,
                ClassTypeTable::NAME,
                ClassTypeTable::SLUG,
                ClassTypeTable::COLOR,
            ]);

        return response()->json([
            'data' => $rows->map(fn($row) => [
                'id'    => $row->{ClassTypeTable::ID},
                'name'  => $row->{ClassTypeTable::NAME},
                'slug'  => $row->{ClassTypeTable::SLUG},
                'color' => $row->{ClassTypeTable::COLOR},
            ])->values()->all(),
        ]);
    }
}
