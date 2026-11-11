<?php

namespace App\Http\Controllers;

use App\Enums\NoteStatus;
use App\Enums\SpaceRole;
use App\Models\Note;
use App\Models\NoteReview;
use App\Models\Space;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // 1. Stats
        $stats = [
            'total_notes' => Note::count(),
            'published_notes' => Note::published()->count(),
            'pending_review' => Note::pendingReview()->count(),
            'my_drafts' => Note::where('owner_id', $user->id)
                ->whereIn('status', [NoteStatus::DRAFT, NoteStatus::CHANGES_REQUESTED])
                ->count(),
        ];

        // 2. Spaces list with user role and notes count
        $spaces = Space::withCount(['notes'])
            ->get()
            ->map(function ($space) use ($user) {
                $role = $user->roleInSpace($space);
                return [
                    'id' => $space->id,
                    'name' => $space->name,
                    'slug' => $space->slug,
                    'description' => $space->description,
                    'icon' => $space->icon,
                    'notes_count' => $space->notes_count,
                    'user_role' => $role?->value,
                    'user_role_label' => $role?->label(),
                ];
            });

        // 3. User's Drafts & Notes
        $myRecentNotes = Note::with('space')
            ->where('owner_id', $user->id)
            ->latest('updated_at')
            ->take(5)
            ->get();

        // 4. Pending Review Queue for this user
        // Accessible if user is admin, or has reviewer/publisher role in any space
        $canReview = $user->isGlobalAdmin() || $user->spaces()->wherePivotIn('role', [
            SpaceRole::REVIEWER->value,
            SpaceRole::PUBLISHER->value,
            SpaceRole::ADMIN->value,
        ])->exists();

        $pendingReviews = [];
        if ($canReview) {
            $pendingReviews = NoteReview::with(['note.space', 'note.owner', 'requester'])
                ->where('status', 'pending')
                ->whereHas('note', function ($query) use ($user) {
                    if (! $user->isGlobalAdmin()) {
                        // User cannot approve their own note (anti self-approval)
                        $query->where('owner_id', '!=', $user->id);
                    }
                })
                ->latest()
                ->take(5)
                ->get();
        }

        // 5. Recent published notes
        $recentPublished = Note::with(['space', 'owner'])
            ->published()
            ->latest('last_reviewed_at')
            ->take(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'spaces' => $spaces,
            'myRecentNotes' => $myRecentNotes,
            'pendingReviews' => $pendingReviews,
            'recentPublished' => $recentPublished,
            'canReview' => $canReview,
        ]);
    }
}
