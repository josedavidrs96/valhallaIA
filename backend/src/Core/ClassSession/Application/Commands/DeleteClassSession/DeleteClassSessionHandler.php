<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Application\Commands\DeleteClassSession;

use App\Src\Core\ClassSession\Domain\Repositories\ClassSessionRepositoryInterface;

final class DeleteClassSessionHandler
{
    public function __construct(private readonly ClassSessionRepositoryInterface $sessions) {}

    public function handle(DeleteClassSessionCommand $command): void
    {
        // Verify session exists before deleting (throws ClassSessionNotFoundException if not found)
        $this->sessions->getById($command->id);

        // TODO (epic-booking): Check if session has bookings before deleting.
        // When epic-booking is implemented, add:
        //   if ($this->bookings->hasBookingsForSession($command->id)) {
        //       throw new SessionHasBookingsException();
        //   }
        // For MVP: no bookings exist, so this guard is always false — skip it.

        $this->sessions->softDelete($command->id);
    }
}
