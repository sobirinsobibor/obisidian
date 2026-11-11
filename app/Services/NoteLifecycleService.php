<?php

namespace App\Services;

use App\Enums\NoteStatus;
use App\Enums\NoteVisibility;
use App\Enums\ReviewStage;
use App\Enums\ReviewStatus;
use App\Models\AuditLog;
use App\Models\Note;
use App\Models\NoteReview;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Validation\ValidationException;

class NoteLifecycleService
{
    public function __construct(
        protected GitVaultService $gitVaultService
    ) {}

    /**
     * Scan content for sensitive secrets or PII (PRD FR-11).
     *
     * @return list<string> Array of detected warning messages
     */
    public function scanContentForSecrets(string $content): array
    {
        $findings = [];

        // Secret key patterns (e.g. AWS keys, GitHub tokens, Bearer tokens, private keys)
        $secretPatterns = [
            'AWS Access Key' => '/AKIA[0-9A-Z]{16}/',
            'GitHub Token' => '/gh[pousr]_[A-Za-z0-9_]{36,255}/',
            'Generic Private Key' => '/-----BEGIN (RSA|EC|OPENSSH|DSA|PGP) PRIVATE KEY-----/',
            'Potential Password Assignment' => '/(?:password|passwd|secret|api_key|token)\s*[:=]\s*["\'][^"\'\s]{8,}["\']/i',
            'Indonesian NIK (16 digit number)' => '/\b[1-9]\d{15}\b/',
        ];

        foreach ($secretPatterns as $label => $pattern) {
            if (preg_match($pattern, $content)) {
                $findings[] = "Terdeteksi potensi data sensitif: {$label}.";
            }
        }

        return $findings;
    }

    /**
     * Submit draft for internal review.
     */
    public function submitForReview(Note $note, User $requester): NoteReview
    {
        if (! in_array($note->status, [NoteStatus::DRAFT, NoteStatus::CHANGES_REQUESTED], true)) {
            throw new Exception("Hanya note berstatus draft atau perlu revisi yang dapat diajukan review.");
        }

        // Validate content secrets
        $secrets = $this->scanContentForSecrets($note->content);
        if (! empty($secrets)) {
            throw ValidationException::withMessages([
                'content' => $secrets,
            ]);
        }

        // Validate required fields
        if (empty(trim($note->content))) {
            throw ValidationException::withMessages([
                'content' => ['Konten catatan tidak boleh kosong.'],
            ]);
        }

        $oldStatus = $note->status;
        $note->update([
            'status' => NoteStatus::IN_REVIEW,
        ]);

        $review = NoteReview::create([
            'note_id' => $note->id,
            'stage' => ReviewStage::INTERNAL,
            'requested_by' => $requester->id,
            'status' => ReviewStatus::PENDING,
        ]);

        AuditLog::record('note.submitted_for_review', $note, [
            'status' => $oldStatus->value,
        ], [
            'status' => NoteStatus::IN_REVIEW->value,
            'review_id' => $review->id,
        ]);

        return $review;
    }

    /**
     * Reviewer requests changes.
     */
    public function requestChanges(Note $note, User $reviewer, string $notes): NoteReview
    {
        $pendingReview = $note->reviews()
            ->where('status', ReviewStatus::PENDING)
            ->latest()
            ->first();

        if ($pendingReview) {
            $pendingReview->update([
                'reviewer_id' => $reviewer->id,
                'status' => ReviewStatus::CHANGES_REQUESTED,
                'resolution_notes' => $notes,
                'resolved_at' => Carbon::now(),
            ]);
        }

        $oldStatus = $note->status;
        $note->update([
            'status' => NoteStatus::CHANGES_REQUESTED,
        ]);

        AuditLog::record('note.changes_requested', $note, [
            'status' => $oldStatus->value,
        ], [
            'status' => NoteStatus::CHANGES_REQUESTED->value,
            'reviewer_id' => $reviewer->id,
            'notes' => $notes,
        ]);

        return $pendingReview ?? new NoteReview();
    }

    /**
     * Approve internal review.
     * Enforces PRD Rule 4.2.a: Anti Self-Approval.
     */
    public function approveInternal(Note $note, User $reviewer, ?string $notes = null): void
    {
        if ($note->owner_id === $reviewer->id) {
            throw new Exception("Pelanggaran aturan anti self-approval: Penulis tidak diizinkan menyetujui catatannya sendiri.");
        }

        $pendingReview = $note->reviews()
            ->where('stage', ReviewStage::INTERNAL)
            ->where('status', ReviewStatus::PENDING)
            ->latest()
            ->first();

        if ($pendingReview) {
            $pendingReview->update([
                'reviewer_id' => $reviewer->id,
                'status' => ReviewStatus::APPROVED,
                'resolution_notes' => $notes,
                'resolved_at' => Carbon::now(),
            ]);
        }

        $now = Carbon::now();
        $reviewDue = $now->copy()->addMonths(3);

        if ($note->visibility === NoteVisibility::PUBLIC) {
            // Note public butuh tahap approval kedua (Publisher)
            $note->update([
                'status' => NoteStatus::IN_PUBLIC_REVIEW,
                'reviewer_id' => $reviewer->id,
                'last_reviewed_at' => $now,
                'review_due_at' => $reviewDue,
            ]);

            NoteReview::create([
                'note_id' => $note->id,
                'stage' => ReviewStage::PUBLIC,
                'requested_by' => $reviewer->id,
                'status' => ReviewStatus::PENDING,
            ]);

            AuditLog::record('note.approved_internal_stage', $note, null, [
                'status' => NoteStatus::IN_PUBLIC_REVIEW->value,
                'reviewer_id' => $reviewer->id,
            ]);
        } else {
            // Note internal/restricted langsung published internal
            $note->update([
                'status' => NoteStatus::PUBLISHED_INTERNAL,
                'reviewer_id' => $reviewer->id,
                'last_reviewed_at' => $now,
                'review_due_at' => $reviewDue,
            ]);

            // Sync to Git Obsidian Vault
            $this->gitVaultService->syncNoteToVault($note);

            AuditLog::record('note.published_internal', $note, null, [
                'status' => NoteStatus::PUBLISHED_INTERNAL->value,
                'reviewer_id' => $reviewer->id,
            ]);
        }
    }

    /**
     * Approve public review (Tahap 2).
     */
    public function approvePublic(Note $note, User $publisher, ?string $notes = null): void
    {
        if ($note->owner_id === $publisher->id) {
            throw new Exception("Pelanggaran aturan anti self-approval: Penulis tidak diizinkan menyetujui catatannya sendiri.");
        }

        $pendingReview = $note->reviews()
            ->where('stage', ReviewStage::PUBLIC)
            ->where('status', ReviewStatus::PENDING)
            ->latest()
            ->first();

        if ($pendingReview) {
            $pendingReview->update([
                'reviewer_id' => $publisher->id,
                'status' => ReviewStatus::APPROVED,
                'resolution_notes' => $notes,
                'resolved_at' => Carbon::now(),
            ]);
        }

        $now = Carbon::now();
        $reviewDue = $now->copy()->addMonths(3);

        $note->update([
            'status' => NoteStatus::PUBLISHED_PUBLIC,
            'publisher_id' => $publisher->id,
            'last_reviewed_at' => $now,
            'review_due_at' => $reviewDue,
        ]);

        // Sync to Git Obsidian Vault
        $this->gitVaultService->syncNoteToVault($note);

        AuditLog::record('note.published_public', $note, null, [
            'status' => NoteStatus::PUBLISHED_PUBLIC->value,
            'publisher_id' => $publisher->id,
        ]);
    }
}
