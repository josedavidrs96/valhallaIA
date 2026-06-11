<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Domain\Exceptions;

final class MemberNotFoundException extends \RuntimeException
{
    public function __construct(string $message = 'Member not found')
    {
        parent::__construct($message);
    }
}
