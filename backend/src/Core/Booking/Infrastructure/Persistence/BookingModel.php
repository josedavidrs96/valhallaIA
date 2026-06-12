<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Infrastructure\Persistence;

use App\Src\Core\Booking\Infrastructure\Tables\BookingTable;
use Illuminate\Database\Eloquent\Model;

final class BookingModel extends Model
{
    protected $table      = BookingTable::TABLE_NAME;
    protected $primaryKey = BookingTable::ID;
    public    $incrementing = false;
    protected $keyType    = 'string';

    protected $fillable = [
        BookingTable::ID,
        BookingTable::MEMBER_ID,
        BookingTable::CLASS_SESSION_ID,
        BookingTable::SESSION_DATE,
        BookingTable::STATUS,
    ];

    protected function casts(): array
    {
        return [
            BookingTable::STATUS => 'string',
        ];
    }
}
