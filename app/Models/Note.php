<?php

namespace App\Models;

use App\Enums\NoteStatus;
use App\Enums\NoteType;
use App\Enums\NoteVisibility;
use App\Enums\SpaceRole;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'kb_id',
        'title',
        'slug',
        'space_id',
        'type',
        'product',
        'visibility',
        'status',
        'owner_id',
        'reviewer_id',
        'publisher_id',
        'last_reviewed_at',
        'review_due_at',
        'tags',
        'content',
        'published_content',
        'git_commit_hash',
        'base_commit_hash',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'type' => NoteType::class,
            'visibility' => NoteVisibility::class,
            'status' => NoteStatus::class,
            'tags' => 'array',
            'last_reviewed_at' => 'date',
            'review_due_at' => 'date',
            'version' => 'integer',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($note) {
            if (empty($note->kb_id)) {
                $maxId = (int) (static::max('id') ?? 0) + 1;
                $note->kb_id = sprintf('kb-%06d', $maxId);
            }

            if (empty($note->slug)) {
                $note->slug = Str::slug($note->title);
            }

            if (empty($note->tags)) {
                $note->tags = [];
            }
        });
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publisher_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(NoteReview::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(NoteComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function getRelativeFilePath(): string
    {
        $spaceSlug = $this->space ? $this->space->slug : 'general';
        $typeSlug = $this->type instanceof NoteType ? $this->type->folderName() : (string) $this->type;

        return "spaces/{$spaceSlug}/{$typeSlug}/{$this->slug}.md";
    }

    public function isPastReviewDue(): bool
    {
        return $this->review_due_at && Carbon::parse($this->review_due_at)->isPast();
    }

    /**
     * Format record into Obsidian-compatible Markdown with YAML Frontmatter
     */
    public function toObsidianMarkdown(): string
    {
        $tagsList = ! empty($this->tags) ? $this->tags : [];
        $yamlTags = empty($tagsList)
            ? '[]'
            : "\n" . implode("\n", array_map(fn ($tag) => "  - {$tag}", $tagsList));

        $ownerIdentifier = $this->owner?->email ?? "usr_{$this->owner_id}";
        $reviewerIdentifier = $this->reviewer?->email ?? ($this->reviewer_id ? "usr_{$this->reviewer_id}" : '~');
        $lastReviewed = $this->last_reviewed_at?->format('Y-m-d') ?? Carbon::now()->format('Y-m-d');
        $reviewDue = $this->review_due_at?->format('Y-m-d') ?? Carbon::now()->addMonths(3)->format('Y-m-d');

        $typeValue = $this->type instanceof NoteType ? $this->type->value : $this->type;
        $visibilityValue = $this->visibility instanceof NoteVisibility ? $this->visibility->value : $this->visibility;
        $statusValue = $this->status instanceof NoteStatus ? $this->status->value : $this->status;
        $spaceSlug = $this->space?->slug ?? 'general';

        $frontmatter = <<<YAML
---
id: {$this->kb_id}
title: "{$this->title}"
space: {$spaceSlug}
type: {$typeValue}
product: {$this->product}
visibility: {$visibilityValue}
status: {$statusValue}
owner: {$ownerIdentifier}
reviewer: {$reviewerIdentifier}
last_reviewed: {$lastReviewed}
review_due: {$reviewDue}
tags: {$yamlTags}
---
YAML;

        return $frontmatter . "\n\n" . trim($this->content) . "\n";
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereIn('status', [
            NoteStatus::PUBLISHED_INTERNAL->value,
            NoteStatus::PUBLISHED_PUBLIC->value,
            NoteStatus::NEEDS_REVIEW->value,
        ]);
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->whereIn('status', [
            NoteStatus::IN_REVIEW->value,
            NoteStatus::IN_PUBLIC_REVIEW->value,
        ]);
    }
}
