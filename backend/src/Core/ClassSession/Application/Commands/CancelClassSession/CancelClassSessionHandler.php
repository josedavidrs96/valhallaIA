<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Application\Commands\CancelClassSession;

use App\Src\Core\ClassSession\Domain\Repositories\ClassSessionRepositoryInterface;

final class CancelClassSessionHandler
{
    public function __construct(private readonly ClassSessionRepositoryInterface $sessions) {}

    public function handle(CancelClassSessionCommand $command): void
    {
        $session = $this->sessions->getById($command->id);

        $session->cancel();

        $this->sessions->save($session);
    }
}
