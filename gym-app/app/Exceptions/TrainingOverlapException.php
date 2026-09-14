<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Training;
use RuntimeException;

final class TrainingOverlapException extends RuntimeException
{
    public function __construct(public readonly Training $conflictingTraining)
    {
        parent::__construct('The requested time overlaps an existing scheduled training.');
    }
}
