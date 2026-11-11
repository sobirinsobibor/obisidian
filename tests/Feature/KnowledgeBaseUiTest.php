<?php

namespace Tests\Feature;

use App\Enums\NoteStatus;
use App\Enums\NoteType;
use App\Enums\NoteVisibility;
use App\Enums\SpaceRole;
use App\Models\Note;
use App\Models\NoteReview;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeBaseUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_dashboard()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Dashboard'));
    }

    public function test_user_can_view_notes_index()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('notes.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Notes/Index'));
    }

    public function test_user_can_view_note_create_page()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('notes.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Notes/Create'));
    }

    public function test_user_can_view_spaces_index_and_show()
    {
        $user = User::factory()->create();
        $space = Space::create(['name' => 'Support', 'slug' => 'support']);

        $response = $this->actingAs($user)->get(route('spaces.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Spaces/Index'));

        $responseShow = $this->actingAs($user)->get(route('spaces.show', $space->slug));
        $responseShow->assertStatus(200);
        $responseShow->assertInertia(fn ($page) => $page->component('Spaces/Show'));
    }

    public function test_user_can_view_reviews_queue_and_workspace()
    {
        $author = User::factory()->create();
        $reviewer = User::factory()->create();
        $space = Space::create(['name' => 'Support', 'slug' => 'support']);

        $note = Note::create([
            'title' => 'Panduan Jaringan',
            'slug' => 'panduan-jaringan',
            'space_id' => $space->id,
            'type' => NoteType::RUNBOOK,
            'visibility' => NoteVisibility::INTERNAL,
            'status' => NoteStatus::IN_REVIEW,
            'owner_id' => $author->id,
            'content' => 'Langkah diagnostik jaringan.',
        ]);

        $review = NoteReview::create([
            'note_id' => $note->id,
            'stage' => 'internal',
            'requested_by' => $author->id,
            'status' => 'pending',
        ]);

        $responseQueue = $this->actingAs($reviewer)->get(route('reviews.index'));
        $responseQueue->assertStatus(200);
        $responseQueue->assertInertia(fn ($page) => $page->component('Reviews/Index'));

        $responseShow = $this->actingAs($reviewer)->get(route('reviews.show', $review->id));
        $responseShow->assertStatus(200);
        $responseShow->assertInertia(fn ($page) => $page->component('Reviews/Show'));
    }
}
