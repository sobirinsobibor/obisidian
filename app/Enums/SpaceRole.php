<?php

namespace App\Enums;

enum SpaceRole: string
{
    case READER = 'reader';
    case CONTRIBUTOR = 'contributor';
    case REVIEWER = 'reviewer';
    case PUBLISHER = 'publisher';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::READER => 'Reader',
            self::CONTRIBUTOR => 'Contributor',
            self::REVIEWER => 'Reviewer',
            self::PUBLISHER => 'Publisher',
            self::ADMIN => 'Admin',
        };
    }

    public function canEdit(): bool
    {
        return in_array($this, [self::CONTRIBUTOR, self::REVIEWER, self::PUBLISHER, self::ADMIN], true);
    }

    public function canReviewInternal(): bool
    {
        return in_array($this, [self::REVIEWER, self::ADMIN], true);
    }

    public function canReviewPublic(): bool
    {
        return in_array($this, [self::PUBLISHER, self::ADMIN], true);
    }
}
