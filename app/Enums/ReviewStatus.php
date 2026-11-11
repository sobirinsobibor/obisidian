<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case CHANGES_REQUESTED = 'changes_requested';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Review',
            self::APPROVED => 'Disetujui',
            self::CHANGES_REQUESTED => 'Diminta Perubahan',
            self::REJECTED => 'Ditolak',
        };
    }
}
