<?php

namespace App\Enums;

enum NoteStatus: string
{
    case DRAFT = 'draft';
    case IN_REVIEW = 'in_review';
    case IN_PUBLIC_REVIEW = 'in_public_review';
    case PUBLISHED_INTERNAL = 'published_internal';
    case PUBLISHED_PUBLIC = 'published_public';
    case CHANGES_REQUESTED = 'changes_requested';
    case NEEDS_REVIEW = 'needs_review';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::IN_REVIEW => 'Menunggu Review Internal',
            self::IN_PUBLIC_REVIEW => 'Menunggu Review Publik',
            self::PUBLISHED_INTERNAL => 'Terbit (Internal)',
            self::PUBLISHED_PUBLIC => 'Terbit (Publik)',
            self::CHANGES_REQUESTED => 'Perlu Revisi',
            self::NEEDS_REVIEW => 'Perlu Review Berkala',
            self::ARCHIVED => 'Diarsipkan',
        };
    }

    public function isPublished(): bool
    {
        return in_array($this, [self::PUBLISHED_INTERNAL, self::PUBLISHED_PUBLIC, self::NEEDS_REVIEW], true);
    }

    public function isPendingReview(): bool
    {
        return in_array($this, [self::IN_REVIEW, self::IN_PUBLIC_REVIEW], true);
    }
}
