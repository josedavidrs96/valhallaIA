<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Infrastructure\Repositories;

use App\Src\Core\Booking\Domain\Entities\Booking;
use App\Src\Core\Booking\Domain\Enums\BookingStatus;
use App\Src\Core\Booking\Domain\Exceptions\BookingNotFoundException;
use App\Src\Core\Booking\Domain\ReadModels\BookingRM;
use App\Src\Core\Booking\Domain\ReadModels\RosterItemRM;
use App\Src\Core\Booking\Domain\Repositories\BookingRepositoryInterface;
use App\Src\Core\Booking\Domain\ValueObjects\BookingId;
use App\Src\Core\Booking\Infrastructure\Hydrators\BookingHydrator;
use App\Src\Core\Booking\Infrastructure\Persistence\BookingModel;
use App\Src\Core\Booking\Infrastructure\Tables\BookingTable;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use Illuminate\Support\Facades\DB;

final class BookingRepository implements BookingRepositoryInterface
{
    public function __construct(private readonly BookingHydrator $hydrator) {}

    public function getById(BookingId $id): Booking
    {
        $model = BookingModel::query()->find($id->value());

        if ($model === null) {
            throw new BookingNotFoundException($id->value());
        }

        return $this->hydrator->hydrate($model);
    }

    public function getByIdRM(BookingId $id): BookingRM
    {
        $row = DB::table('bookings as b')
            ->join('class_sessions as cs', 'cs.id', '=', 'b.class_session_id')
            ->join('class_types as ct', 'ct.id', '=', 'cs.class_type_id')
            ->select(
                'b.id',
                'b.member_id',
                'b.class_session_id',
                'b.status',
                'b.created_at',
                'cs.day_of_week',
                'cs.time_slot',
                'ct.name as class_type_name',
                'ct.slug as class_type_slug'
            )
            ->where('b.id', $id->value())
            ->first();

        if ($row === null) {
            throw new BookingNotFoundException($id->value());
        }

        return new BookingRM(
            id:             $row->id,
            memberId:       $row->member_id,
            classSessionId: $row->class_session_id,
            status:         $row->status,
            dayOfWeek:      $row->day_of_week,
            timeSlot:       $row->time_slot,
            classTypeName:  $row->class_type_name,
            classTypeSlug:  $row->class_type_slug,
            createdAt:      $row->created_at ?? null,
        );
    }

    public function findByMemberAndSession(MemberId $memberId, ClassSessionId $sessionId): ?Booking
    {
        $model = BookingModel::query()
            ->where(BookingTable::MEMBER_ID, $memberId->value())
            ->where(BookingTable::CLASS_SESSION_ID, $sessionId->value())
            ->where(BookingTable::STATUS, BookingStatus::Confirmed->value)
            ->first();

        return $model ? $this->hydrator->hydrate($model) : null;
    }

    public function countConfirmedBySession(ClassSessionId $sessionId): int
    {
        return BookingModel::query()
            ->where(BookingTable::CLASS_SESSION_ID, $sessionId->value())
            ->where(BookingTable::STATUS, BookingStatus::Confirmed->value)
            ->count();
    }

    public function save(Booking $booking): void
    {
        BookingModel::query()->updateOrCreate(
            [BookingTable::ID => $booking->id->value()],
            $this->hydrator->dehydrate($booking),
        );
    }

    public function findByMember(MemberId $memberId): array
    {
        $rows = DB::table('bookings as b')
            ->join('class_sessions as cs', 'cs.id', '=', 'b.class_session_id')
            ->join('class_types as ct', 'ct.id', '=', 'cs.class_type_id')
            ->select(
                'b.id',
                'b.member_id',
                'b.class_session_id',
                'b.status',
                'b.created_at',
                'cs.day_of_week',
                'cs.time_slot',
                'ct.name as class_type_name',
                'ct.slug as class_type_slug'
            )
            ->where('b.member_id', $memberId->value())
            ->orderBy('b.created_at', 'desc')
            ->get();

        return $rows->map(fn($row) => new BookingRM(
            id:             $row->id,
            memberId:       $row->member_id,
            classSessionId: $row->class_session_id,
            status:         $row->status,
            dayOfWeek:      $row->day_of_week,
            timeSlot:       $row->time_slot,
            classTypeName:  $row->class_type_name,
            classTypeSlug:  $row->class_type_slug,
            createdAt:      $row->created_at ?? null,
        ))->all();
    }

    public function getRoster(ClassSessionId $sessionId): array
    {
        $rows = DB::table('bookings as b')
            ->join('members as m', 'm.id', '=', 'b.member_id')
            ->select(
                'b.id as booking_id',
                'b.member_id',
                'b.status',
                'b.created_at as booked_at',
                'm.member_number',
                'm.first_name',
                'm.last_name'
            )
            ->where('b.class_session_id', $sessionId->value())
            ->orderBy('b.created_at', 'asc')
            ->get();

        return $rows->map(fn($row) => new RosterItemRM(
            bookingId:    $row->booking_id,
            memberId:     $row->member_id,
            memberNumber: (int) $row->member_number,
            firstName:    $row->first_name,
            lastName:     $row->last_name,
            status:       $row->status,
            bookedAt:     $row->booked_at ?? null,
        ))->all();
    }
}
