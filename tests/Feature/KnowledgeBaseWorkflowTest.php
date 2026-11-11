<?php

namespace Tests\Feature;

use App\Enums\NoteStatus;
use App\Enums\NoteType;
use App\Enums\NoteVisibility;
use App\Models\Note;
use App\Models\Space;
use App\Models\User;
use App\Services\GitVaultService;
use App\Services\NoteLifecycleService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class KnowledgeBaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected string $testVaultPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testVaultPath = storage_path('testing_vault_' . uniqid());
        config(['knowledgebase.vault_path' => $this->testVaultPath]);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->testVaultPath)) {
            exec('attrib -r "' . $this->testVaultPath . '\*" /s');
            File::deleteDirectory($this->testVaultPath);
        }
        parent::tearDown();
    }

    public function test_contributor_can_submit_valid_note_for_review()
    {
        $author = User::factory()->create();
        $space = Space::create(['name' => 'Support', 'slug' => 'support']);

        $note = Note::create([
            'title' => 'Panduan Reset Akun',
            'slug' => 'panduan-reset-akun',
            'space_id' => $space->id,
            'type' => NoteType::RUNBOOK,
            'visibility' => NoteVisibility::INTERNAL,
            'status' => NoteStatus::DRAFT,
            'owner_id' => $author->id,
            'content' => 'Langkah reset akun pengguna terdaftar.',
        ]);

        $service = app(NoteLifecycleService::class);
        $review = $service->submitForReview($note, $author);

        $this->assertEquals(NoteStatus::IN_REVIEW, $note->fresh()->status);
        $this->assertDatabaseHas('note_reviews', [
            'note_id' => $note->id,
            'requested_by' => $author->id,
            'status' => 'pending',
        ]);
    }

    public function test_secret_scanning_blocks_submission_with_sensitive_keys()
    {
        $this->expectException(ValidationException::class);

        $author = User::factory()->create();
        $space = Space::create(['name' => 'Security', 'slug' => 'security']);

        $note = Note::create([
            'title' => 'Konfigurasi Server',
            'slug' => 'konfigurasi-server',
            'space_id' => $space->id,
            'type' => NoteType::SPEC,
            'visibility' => NoteVisibility::INTERNAL,
            'status' => NoteStatus::DRAFT,
            'owner_id' => $author->id,
            'content' => 'Gunakan AWS Key ini: AKIAIOSFODNN7EXAMPLE untuk login.',
        ]);

        $service = app(NoteLifecycleService::class);
        $service->submitForReview($note, $author);
    }

    public function test_anti_self_approval_rule_prevents_author_from_approving_own_note()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('anti self-approval');

        $author = User::factory()->create();
        $space = Space::create(['name' => 'Support', 'slug' => 'support']);

        $note = Note::create([
            'title' => 'Troubleshooting Jaringan',
            'slug' => 'troubleshooting-jaringan',
            'space_id' => $space->id,
            'type' => NoteType::TROUBLESHOOTING,
            'visibility' => NoteVisibility::INTERNAL,
            'status' => NoteStatus::IN_REVIEW,
            'owner_id' => $author->id,
            'content' => 'Periksa kabel ethernet dan gateway.',
        ]);

        $service = app(NoteLifecycleService::class);
        $service->approveInternal($note, $author); // Should throw anti self-approval exception!
    }

    public function test_supervisor_approval_publishes_note_and_commits_to_obsidian_vault()
    {
        $author = User::factory()->create(['name' => 'John Doe', 'email' => 'john@test.com']);
        $supervisor = User::factory()->create(['name' => 'Alice Supervisor', 'email' => 'alice@test.com']);
        $space = Space::create(['name' => 'Support', 'slug' => 'support']);

        $note = Note::create([
            'title' => 'Panduan SOP Tiket',
            'slug' => 'panduan-sop-tiket',
            'space_id' => $space->id,
            'type' => NoteType::RUNBOOK,
            'visibility' => NoteVisibility::INTERNAL,
            'status' => NoteStatus::DRAFT,
            'owner_id' => $author->id,
            'content' => "## Langkah SOP\n\n1. Tanggapi tiket dalam 15 menit.\n2. Rujuk [[kb-000001]] untuk panduan teknis.",
        ]);

        $service = app(NoteLifecycleService::class);
        $service->submitForReview($note, $author);
        $service->approveInternal($note, $supervisor, 'Dokumen runbook sangat jelas dan akurat.');

        $freshNote = $note->fresh();
        $this->assertEquals(NoteStatus::PUBLISHED_INTERNAL, $freshNote->status);
        $this->assertEquals($supervisor->id, $freshNote->reviewer_id);
        $this->assertNotEmpty($freshNote->git_commit_hash);

        // Verify file was written to the Obsidian vault directory structure
        $expectedFilePath = $this->testVaultPath . '/spaces/support/runbook/panduan-sop-tiket.md';
        $this->assertFileExists($expectedFilePath);

        $fileContent = File::get($expectedFilePath);
        $this->assertStringContainsString('id: ' . $freshNote->kb_id, $fileContent);
        $this->assertStringContainsString('title: "Panduan SOP Tiket"', $fileContent);
        $this->assertStringContainsString('type: runbook', $fileContent);
        $this->assertStringContainsString('status: published_internal', $fileContent);
        $this->assertStringContainsString('Langkah SOP', $fileContent);
    }
}
