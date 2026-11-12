<?php

namespace App\Http\Controllers;

use App\Enums\ReviewStage;
use App\Enums\ReviewStatus;
use App\Models\AuditLog;
use App\Models\NoteComment;
use App\Models\NoteReview;
use App\Services\NoteLifecycleService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function __construct(
        protected NoteLifecycleService $lifecycleService
    ) {}

    /**
     * Display review inbox for reviewers / publishers.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_if(! $user->canReviewAnySpace(), 403, 'Anda tidak memiliki hak akses untuk meninjau dokumen.');

        $reviews = NoteReview::with(['note.space', 'note.owner', 'requester'])
            ->where('status', ReviewStatus::PENDING)
            ->whereHas('note', function ($query) use ($user) {
                if (! $user->isGlobalAdmin()) {
                    // Anti self-approval: exclude own notes from review queue
                    $query->where('owner_id', '!=', $user->id);
                }
            })
            ->latest()
            ->paginate(15);

        $history = NoteReview::with(['note.space', 'note.owner', 'reviewer'])
            ->whereIn('status', [ReviewStatus::APPROVED, ReviewStatus::CHANGES_REQUESTED, ReviewStatus::REJECTED])
            ->latest('resolved_at')
            ->take(10)
            ->get();

        return Inertia::render('Reviews/Index', [
            'reviews' => $reviews,
            'history' => $history,
        ]);
    }

    /**
     * Review Workspace for a specific review request.
     */
    public function show(NoteReview $review, Request $request): Response
    {
        $user = $request->user();
        abort_if(! $user->canReviewAnySpace(), 403, 'Anda tidak memiliki hak akses untuk meninjau dokumen.');

        $review->load([
            'note.space',
            'note.owner',
            'note.reviewer',
            'requester',
            'comments.user',
        ]);

        $note = $review->note;
        $user = $request->user();

        // Enforce anti self-approval rule on UI level
        $isSelf = $note->owner_id === $user->id;

        return Inertia::render('Reviews/Show', [
            'review' => $review,
            'note' => $note,
            'isSelf' => $isSelf,
            'comments' => $note->comments()->with('user')->get(),
        ]);
    }

    /**
     * Approve review.
     */
    public function approve(NoteReview $review, Request $request): RedirectResponse
    {
        $user = $request->user();
        $note = $review->note;

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            if ($review->stage === ReviewStage::INTERNAL) {
                $this->lifecycleService->approveInternal($note, $user, $validated['notes'] ?? null);
                $message = 'Review internal disetujui. ' . ($note->visibility->isPublic() ? 'Catatan dialihkan ke antrean review publik.' : 'Catatan berhasil diterbitkan dan disinkronkan ke Obsidian Vault.');
            } else {
                $this->lifecycleService->approvePublic($note, $user, $validated['notes'] ?? null);
                $message = 'Review publik disetujui. Catatan resmi diterbitkan dan disinkronkan ke Obsidian Vault.';
            }

            return redirect()->route('reviews.index')->with('success', $message);
        } catch (Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Request changes from author.
     */
    public function requestChanges(NoteReview $review, Request $request): RedirectResponse
    {
        $user = $request->user();
        $note = $review->note;

        $validated = $request->validate([
            'notes' => 'required|string|max:2000',
        ]);

        $this->lifecycleService->requestChanges($note, $user, $validated['notes']);

        return redirect()->route('reviews.index')
            ->with('success', 'Permintaan revisi telah dikirimkan kembali kepada penulis.');
    }

    /**
     * Post a comment on the note / review.
     */
    public function storeComment(NoteReview $review, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'comment' => 'required|string|max:2000',
            'line_number' => 'nullable|integer',
            'selected_text' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        NoteComment::create([
            'note_id' => $review->note_id,
            'review_id' => $review->id,
            'user_id' => $user->id,
            'line_number' => $validated['line_number'] ?? null,
            'selected_text' => $validated['selected_text'] ?? null,
            'comment' => $validated['comment'],
        ]);

        AuditLog::record('review.comment_added', $review->note, null, [
            'comment' => Str::limit($validated['comment'], 100),
            'line_number' => $validated['line_number'] ?? null,
        ]);

        return back()->with('success', 'Komentar berhasil ditambahkan.');
    }
}
