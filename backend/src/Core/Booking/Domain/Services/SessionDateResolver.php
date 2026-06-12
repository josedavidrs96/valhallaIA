<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Domain\Services;

use App\Src\Core\ClassSession\Domain\Enums\DayOfWeek;
use App\Src\Core\ClassSession\Domain\ValueObjects\TimeSlot;

final class SessionDateResolver
{
    private const DAY_ISO = [
        DayOfWeek::Monday->value    => 1,
        DayOfWeek::Tuesday->value   => 2,
        DayOfWeek::Wednesday->value => 3,
        DayOfWeek::Thursday->value  => 4,
        DayOfWeek::Friday->value    => 5,
    ];

    public function resolve(DayOfWeek $dayOfWeek, TimeSlot $timeSlot, \DateTimeImmutable $now): \DateTimeImmutable
    {
        $targetIso  = self::DAY_ISO[$dayOfWeek->value];
        $currentIso = (int) $now->format('N');
        $diff       = $targetIso - $currentIso;

        $candidate = $now->modify("{$diff} days")->setTime(0, 0, 0);

        [$h, $m]           = explode(':', $timeSlot->value);
        $candidateDatetime = $candidate->setTime((int) $h, (int) $m, 0);

        if ($candidateDatetime <= $now) {
            $candidate = $candidate->modify('+7 days');
        }

        return $candidate;
    }
}
