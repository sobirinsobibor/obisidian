<?php

namespace App\Models;

use App\Enums\ReviewStage;
use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NoteReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'stage',
        'requested_by',
        'reviewer_id',
        'status',
        'resolution_notes',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'stage' => ReviewStage::class,
            'status' => ReviewStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(NoteComment::class, 'review_id');
    }
}
