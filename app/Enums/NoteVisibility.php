<?php

namespace App\Enums;

enum NoteVisibility: string
{
    case INTERNAL = 'internal';
    case PUBLIC = 'public';
    case RESTRICTED = 'restricted';

    public function label(): string
    {
        return match ($this) {
            self::INTERNAL => 'Internal',
            self::PUBLIC => 'Publik',
            self::RESTRICTED => 'Restricted (Terbatas)',
        };
    }

    public function isPublic(): bool
    {
        return $this === self::PUBLIC;
    }

    public function isRestricted(): bool
    {
        return $this === self::RESTRICTED;
    }
}
