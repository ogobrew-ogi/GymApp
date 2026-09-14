<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\GymClosure;
use RuntimeException;

final class GymClosedException extends RuntimeException
{
    public function __construct(public readonly GymClosure $closure)
    {
        parent::__construct('The gym is closed during the requested time.');
    }
}
