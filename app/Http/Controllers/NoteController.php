<?php

namespace App\Http\Controllers;

use App\Enums\NoteStatus;
use App\Enums\NoteType;
use App\Enums\NoteVisibility;
use App\Enums\SpaceRole;
use App\Models\AuditLog;
use App\Models\Note;
use App\Models\Space;
use App\Services\NoteLifecycleService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    public function __construct(
        protected NoteLifecycleService $lifecycleService
    ) {}

    /**
     * Display a listing of knowledge base notes (Explorer).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = Note::with(['space', 'owner', 'reviewer']);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('kb_id', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($spaceSlug = $request->input('space')) {
            $query->whereHas('space', fn ($q) => $q->where('slug', $spaceSlug));
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($visibility = $request->input('visibility')) {
            $query->where('visibility', $visibility);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $notes = $query->latest('updated_at')->paginate(12)->withQueryString();

        return Inertia::render('Notes/Index', [
            'notes' => $notes,
            'spaces' => Space::select('id', 'name', 'slug')->get(),
            'types' => collect(NoteType::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ]),
            'visibilities' => collect(NoteVisibility::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ]),
            'statuses' => collect(NoteStatus::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ]),
            'filters' => $request->only(['search', 'space', 'type', 'visibility', 'status']),
        ]);
    }

    /**
     * Show note creation form.
     */
    public function create(Request $request): Response
    {
        $user = $request->user();

        // Templates definition (PRD FR-05)
        $templates = [
            [
                'type' => NoteType::FAQ->value,
                'label' => 'FAQ (Frequently Asked Questions)',
                'content' => "## Pertanyaan Umum\n\n### Q: Apa pertanyaan utamanya?\nA: Jawaban jelas dan ringkas di sini.\n\n### Q: Apa pertanyaan lanjutan?\nA: Penjelasan tambahan atau tautan ke [[dokumen-terkait]].",
            ],
            [
                'type' => NoteType::RUNBOOK->value,
                'label' => 'Runbook / SOP Operasional',
                'content' => "## Ringkasan Prosedur\nPanduan langkah-demi-langkah penanganan operasional.\n\n> [!NOTE]\n> Pastikan Anda memiliki akses yang diperlukan sebelum menjalankan instruksi berikut.\n\n## Langkah-langkah\n1. Langkah pertama.\n2. Langkah kedua.\n3. Verifikasi hasil akhir.",
            ],
            [
                'type' => NoteType::SPEC->value,
                'label' => 'Spesifikasi Fitur',
                'content' => "## Latar Belakang & Masalah\nDeskripsi kebutuhan atau masalah pengguna yang ingin diselesaikan.\n\n## Ruang Lingkup Fitur\n- Cakupan fitur v1.\n- Kebutuhan teknis.\n\n> [!IMPORTANT]\n> Batasan keamanan dan performa yang harus dipenuhi.",
            ],
            [
                'type' => NoteType::RELEASE_NOTE->value,
                'label' => 'Release Note',
                'content' => "## Ringkasan Rilis (vX.Y.Z)\nTanggal rilis: " . date('Y-m-d') . "\n\n### 🚀 Fitur Baru\n- Fitur A\n- Fitur B\n\n### 🐛 Perbaikan Bug\n- Bug 1 terselesaikan.",
            ],
            [
                'type' => NoteType::TROUBLESHOOTING->value,
                'label' => 'Panduan Troubleshooting',
                'content' => "## Gejala Masalah\nDeskripsi pesan kesalahan atau error yang muncul di log.\n\n## Kemungkinan Penyebab\n1. Penyebab kemungkinan 1.\n2. Penyebab kemungkinan 2.\n\n## Solusi & Penanganan\n> [!TIP]\n> Coba langkah diagnostik awal sebelum merestart service.",
            ],
        ];

        return Inertia::render('Notes/Create', [
            'spaces' => Space::all(),
            'types' => collect(NoteType::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ]),
            'visibilities' => collect(NoteVisibility::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ]),
            'templates' => $templates,
            'defaultSpace' => $request->query('space'),
        ]);
    }

    /**
     * Store newly created note.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'space_id' => 'required|exists:spaces,id',
            'type' => 'required|string',
            'product' => 'required|string|max:100',
            'visibility' => 'required|string',
            'content' => 'required|string',
            'tags' => 'nullable|array',
            'action' => 'required|in:save_draft,submit_review',
        ]);

        $user = $request->user();

        // Check secret scanning
        $warnings = $this->lifecycleService->scanContentForSecrets($validated['content']);
        if (! empty($warnings)) {
            return back()->withErrors(['content' => $warnings])->withInput();
        }

        $note = Note::create([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'space_id' => $validated['space_id'],
            'type' => $validated['type'],
            'product' => $validated['product'],
            'visibility' => $validated['visibility'],
            'status' => NoteStatus::DRAFT,
            'owner_id' => $user->id,
            'tags' => $validated['tags'] ?? [],
            'content' => $validated['content'],
            'review_due_at' => Carbon::now()->addMonths(3),
        ]);

        AuditLog::record('note.created', $note, null, [
            'title' => $note->title,
            'space_id' => $note->space_id,
        ]);

        if ($validated['action'] === 'submit_review') {
            $this->lifecycleService->submitForReview($note, $user);
            return redirect()->route('notes.show', $note->id)
                ->with('success', 'Catatan berhasil dibuat dan diajukan untuk review.');
        }

        return redirect()->route('notes.show', $note->id)
            ->with('success', 'Draft catatan berhasil disimpan.');
    }

    /**
     * Display a single note with metadata & markdown view.
     */
    public function show(Note $note, Request $request): Response
    {
        $note->load([
            'space',
            'owner',
            'reviewer',
            'publisher',
            'reviews.requester',
            'reviews.reviewer',
            'comments.user',
            'attachments',
        ]);

        $user = $request->user();
        $userRole = $user->roleInSpace($note->space);

        $canEdit = $user->id === $note->owner_id || $user->isGlobalAdmin();
        $canReview = ! $canEdit && $user->hasRoleInSpace($note->space, [
            SpaceRole::REVIEWER,
            SpaceRole::PUBLISHER,
            SpaceRole::ADMIN,
        ]);

        // Audit trail
        $auditLogs = AuditLog::with('user')
            ->where('auditable_type', Note::class)
            ->where('auditable_id', $note->id)
            ->latest('created_at')
            ->take(10)
            ->get();

        return Inertia::render('Notes/Show', [
            'note' => $note,
            'canEdit' => $canEdit,
            'canReview' => $canReview,
            'userRole' => $userRole?->value,
            'auditLogs' => $auditLogs,
        ]);
    }

    /**
     * Show edit form.
     */
    public function edit(Note $note, Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Notes/Edit', [
            'note' => $note->load('space'),
            'spaces' => Space::all(),
            'types' => collect(NoteType::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ]),
            'visibilities' => collect(NoteVisibility::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ]),
        ]);
    }

    /**
     * Update note.
     */
    public function update(Request $request, Note $note): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'space_id' => 'required|exists:spaces,id',
            'type' => 'required|string',
            'product' => 'required|string|max:100',
            'visibility' => 'required|string',
            'content' => 'required|string',
            'tags' => 'nullable|array',
            'action' => 'required|in:save_draft,submit_review',
        ]);

        $user = $request->user();

        // Scan secrets
        $warnings = $this->lifecycleService->scanContentForSecrets($validated['content']);
        if (! empty($warnings)) {
            return back()->withErrors(['content' => $warnings])->withInput();
        }

        $oldValues = $note->only(['title', 'space_id', 'type', 'visibility', 'content', 'status']);

        $note->update([
            'title' => $validated['title'],
            'space_id' => $validated['space_id'],
            'type' => $validated['type'],
            'product' => $validated['product'],
            'visibility' => $validated['visibility'],
            'tags' => $validated['tags'] ?? [],
            'content' => $validated['content'],
        ]);

        AuditLog::record('note.updated', $note, $oldValues, $note->only(['title', 'space_id', 'type', 'visibility', 'content']));

        if ($validated['action'] === 'submit_review') {
            $this->lifecycleService->submitForReview($note, $user);
            return redirect()->route('notes.show', $note->id)
                ->with('success', 'Perubahan berhasil disimpan dan diajukan untuk review.');
        }

        return redirect()->route('notes.show', $note->id)
            ->with('success', 'Perubahan draft berhasil disimpan.');
    }

    /**
     * Submit for review action directly from note page.
     */
    public function submitReview(Note $note, Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->lifecycleService->submitForReview($note, $user);

        return redirect()->route('notes.show', $note->id)
            ->with('success', 'Catatan berhasil diajukan untuk peninjauan (review).');
    }
}
