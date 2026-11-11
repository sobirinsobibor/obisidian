<?php

namespace App\Enums;

enum NoteType: string
{
    case FAQ = 'faq';
    case RUNBOOK = 'runbook';
    case SPEC = 'spec';
    case RELEASE_NOTE = 'release-note';
    case TROUBLESHOOTING = 'troubleshooting';

    public function label(): string
    {
        return match ($this) {
            self::FAQ => 'FAQ',
            self::RUNBOOK => 'Runbook',
            self::SPEC => 'Spesifikasi Fitur',
            self::RELEASE_NOTE => 'Release Note',
            self::TROUBLESHOOTING => 'Troubleshooting',
        };
    }

    public function folderName(): string
    {
        return $this->value;
    }
}
