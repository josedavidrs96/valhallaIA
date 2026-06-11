<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Application\Commands\RestoreClassSession;

use App\Src\Core\ClassSession\Domain\Repositories\ClassSessionRepositoryInterface;

final class RestoreClassSessionHandler
{
    public function __construct(private readonly ClassSessionRepositoryInterface $sessions) {}

    public function handle(RestoreClassSessionCommand $command): void
    {
        $session = $this->sessions->getById($command->id);

        $session->restore();

        $this->sessions->save($session);
    }
}
