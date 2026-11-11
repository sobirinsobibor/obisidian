<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('kb_id', 30)->unique();
            $table->string('title', 255);
            $table->string('slug', 255);
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('product', 100)->default('general');
            $table->string('visibility', 50)->default('internal');
            $table->string('status', 50)->default('draft');
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('publisher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('last_reviewed_at')->nullable();
            $table->date('review_due_at')->nullable();
            $table->json('tags')->nullable();
            $table->longText('content');
            $table->longText('published_content')->nullable();
            $table->string('git_commit_hash', 64)->nullable();
            $table->string('base_commit_hash', 64)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['space_id', 'type', 'slug']);
            $table->index(['status', 'review_due_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
