<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Application\Queries\GetClassSessionById;

use App\Src\Core\ClassSession\Domain\ReadModels\ClassSessionRM;
use App\Src\Core\ClassSession\Domain\Repositories\ClassSessionRepositoryInterface;

final class GetClassSessionByIdHandler
{
    public function __construct(private readonly ClassSessionRepositoryInterface $sessions) {}

    public function handle(GetClassSessionByIdQuery $query): ClassSessionRM
    {
        return $this->sessions->getByIdRM($query->id);
    }
}
