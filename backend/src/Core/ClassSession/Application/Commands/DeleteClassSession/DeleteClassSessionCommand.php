<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Application\Commands\DeleteClassSession;

use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;

final readonly class DeleteClassSessionCommand
{
    public function __construct(
        public ClassSessionId $id,
    ) {}
}
