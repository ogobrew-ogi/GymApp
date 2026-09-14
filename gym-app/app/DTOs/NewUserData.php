<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class NewUserData
{
    public function __construct(
        public string $name,
        public string $username,
        public bool $isAdmin = false,
    ) {}
}
