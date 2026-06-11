<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Infrastructure\Tables;

final class BookingTable
{
    public const TABLE_NAME       = 'bookings';
    public const ID               = 'id';
    public const MEMBER_ID        = 'member_id';
    public const CLASS_SESSION_ID = 'class_session_id';
    public const STATUS           = 'status';
    public const CREATED_AT       = 'created_at';
    public const UPDATED_AT       = 'updated_at';
}
