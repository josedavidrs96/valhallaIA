<?php

declare(strict_types=1);

namespace App\Http\Actions\Staff;

use App\Src\Core\Staff\Infrastructure\Tables\StaffTable;
use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class ListCoachesAction
{
    public function __invoke(): JsonResponse
    {
        $rows = DB::table(StaffTable::TABLE_NAME . ' as s')
            ->join(UserTable::TABLE_NAME . ' as u', 'u.' . UserTable::ID, '=', 's.' . StaffTable::USER_ID)
            ->where('u.' . UserTable::ROLE, 'coach')
            ->whereNull('u.' . UserTable::DELETED_AT)
            ->orderBy('s.' . StaffTable::FIRST_NAME)
            ->get([
                's.' . StaffTable::ID       . ' as id',
                's.' . StaffTable::USER_ID  . ' as user_id',
                's.' . StaffTable::FIRST_NAME . ' as first_name',
                's.' . StaffTable::LAST_NAME  . ' as last_name',
                'u.' . UserTable::EMAIL . ' as email',
            ]);

        return response()->json([
            'data' => $rows->map(fn($row) => [
                'id'         => $row->id,
                'user_id'    => $row->user_id,
                'first_name' => $row->first_name,
                'last_name'  => $row->last_name,
                'email'      => $row->email,
            ])->values()->all(),
        ]);
    }
}
