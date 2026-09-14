<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Represents a User's role in the domain vocabulary.
 *
 * NOT a stored column — derived from User::is_admin (Domain Model Spec
 * §5.1). Kept as an enum purely so code/policies can express intent as
 * UserRole::Admin instead of a raw boolean check, without introducing a
 * second, independently-stored source of truth.
 */
enum UserRole: string
{
    case Member = 'member';
    case Admin = 'admin';

    public static function fromIsAdmin(bool $isAdmin): self
    {
        return $isAdmin ? self::Admin : self::Member;
    }

    public function label(): string
    {
        return match ($this) {
            self::Member => 'Member',
            self::Admin => 'Administrator',
        };
    }
}
