<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Application\Commands\CreateClassSession;

use App\Src\Core\ClassSession\Domain\Entities\ClassSession;
use App\Src\Core\ClassSession\Domain\Exceptions\ClassTypeNotFoundException;
use App\Src\Core\ClassSession\Domain\Exceptions\CoachConflictException;
use App\Src\Core\ClassSession\Domain\Exceptions\CoachNotFoundException;
use App\Src\Core\ClassSession\Domain\Repositories\ClassSessionRepositoryInterface;
use App\Src\Core\ClassType\Infrastructure\Tables\ClassTypeTable;
use App\Src\Shared\Auth\Domain\Enums\UserRole;
use App\Src\Shared\Auth\Domain\Exceptions\UserNotFoundException;
use App\Src\Shared\Auth\Domain\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class CreateClassSessionHandler
{
    public function __construct(
        private readonly ClassSessionRepositoryInterface $sessions,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function handle(CreateClassSessionCommand $command): void
    {
        // Cross-BC existence check: pragmatic exception — no ClassTypeRepositoryInterface exists yet.
        // Using DB query directly in handler as documented controlled exception for cross-BC reads.
        $classTypeExists = DB::table(ClassTypeTable::TABLE_NAME)
            ->where(ClassTypeTable::ID, $command->classTypeId->value())
            ->where(ClassTypeTable::IS_ACTIVE, 1)
            ->exists();

        if (!$classTypeExists) {
            throw new ClassTypeNotFoundException($command->classTypeId->value());
        }

        if ($command->coachId !== null) {
            try {
                $coach = $this->users->getById($command->coachId);
            } catch (UserNotFoundException) {
                throw new CoachNotFoundException($command->coachId->value());
            }

            if ($coach->role !== UserRole::Coach) {
                throw new CoachNotFoundException($command->coachId->value());
            }

            if ($this->sessions->hasCoachConflict($command->coachId, $command->dayOfWeek, $command->timeSlot, null)) {
                throw new CoachConflictException();
            }
        }

        $session = ClassSession::create(
            id:          $command->id,
            classTypeId: $command->classTypeId,
            coachId:     $command->coachId,
            dayOfWeek:   $command->dayOfWeek,
            timeSlot:    $command->timeSlot,
            maxCapacity: $command->maxCapacity,
        );

        $this->sessions->save($session);
    }
}
