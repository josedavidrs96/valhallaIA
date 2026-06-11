<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Application\Queries\GetClassSessionById;

use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;

final readonly class GetClassSessionByIdQuery
{
    public function __construct(
        public ClassSessionId $id,
    ) {}
}
