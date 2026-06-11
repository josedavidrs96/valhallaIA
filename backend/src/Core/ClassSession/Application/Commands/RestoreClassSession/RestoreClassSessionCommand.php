<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Application\Commands\RestoreClassSession;

use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;

final readonly class RestoreClassSessionCommand
{
    public function __construct(
        public ClassSessionId $id,
    ) {}
}
