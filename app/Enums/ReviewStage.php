<?php

namespace App\Enums;

enum ReviewStage: string
{
    case INTERNAL = 'internal';
    case PUBLIC = 'public';

    public function label(): string
    {
        return match ($this) {
            self::INTERNAL => 'Review Internal (Akurasi Teknis)',
            self::PUBLIC => 'Review Publik (Kebijakan/Branding)',
        };
    }
}
