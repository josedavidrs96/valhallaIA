<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Application\Commands\CancelClassSession;

use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;

final readonly class CancelClassSessionCommand
{
    public function __construct(
        public ClassSessionId $id,
    ) {}
}
