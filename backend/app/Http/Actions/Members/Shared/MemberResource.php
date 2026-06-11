<?php

declare(strict_types=1);

namespace App\Http\Actions\Members\Shared;

use App\Src\Core\Member\Domain\ReadModels\MemberDetailRM;
use Illuminate\Http\JsonResponse;

final class MemberResource
{
    public function __construct(private readonly MemberDetailRM $rm) {}

    public function toResponse(int $status = 200): JsonResponse
    {
        return response()->json([
            'id'                      => $this->rm->id,
            'user_id'                 => $this->rm->userId,
            'member_number'           => $this->rm->memberNumber,
            'first_name'              => $this->rm->firstName,
            'last_name'               => $this->rm->lastName,
            'email'                   => $this->rm->email,
            'phone'                   => $this->rm->phone,
            'date_of_birth'           => $this->rm->dateOfBirth,
            'profile_photo'           => $this->rm->profilePhoto,
            'join_date'               => $this->rm->joinDate,
            'status'                  => $this->rm->status,
            'emergency_contact_name'  => $this->rm->emergencyContactName,
            'emergency_contact_phone' => $this->rm->emergencyContactPhone,
            'notes'                   => $this->rm->notes,
            'created_at'              => $this->rm->createdAt,
            'plan'                    => $this->rm->planId ? [
                'id'                => $this->rm->planId,
                'name'              => $this->rm->planName,
                'price_cents'       => $this->rm->planPriceCents,
                'classes_per_month' => $this->rm->planClassesPerMonth,
            ] : null,
        ], $status);
    }
}
