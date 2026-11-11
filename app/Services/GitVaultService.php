<?php

namespace App\Services;

use App\Enums\NoteStatus;
use App\Enums\NoteType;
use App\Enums\NoteVisibility;
use App\Models\AuditLog;
use App\Models\Note;
use App\Models\Space;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class GitVaultService
{
    protected string $vaultPath;

    public function __construct()
    {
        // By default use storage_path('vault') or env('OBSIDIAN_VAULT_PATH')
        $this->vaultPath = config('knowledgebase.vault_path', base_path('vault'));
    }

    public function getVaultPath(): string
    {
        return $this->vaultPath;
    }

    /**
     * Ensure the vault folder and git repo are initialized.
     */
    public function initializeVault(): void
    {
        if (! File::exists($this->vaultPath)) {
            File::makeDirectory($this->vaultPath, 0755, true);
        }

        $gitHead = $this->vaultPath . DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR . 'HEAD';
        if (! File::exists($gitHead)) {
            $this->runGitCommand(['init']);
            $this->runGitCommand(['config', 'user.name', 'Knowledge Base Portal']);
            $this->runGitCommand(['config', 'user.email', 'kb-portal@system.local']);

            // Create initial README
            File::put($this->vaultPath . DIRECTORY_SEPARATOR . 'README.md', "# Knowledge Base Obsidian Vault\n\nManaged automatically by Knowledge Base Portal.");
            $this->runGitCommand(['add', 'README.md']);
            $this->runGitCommand(['commit', '-m', 'chore: initialize obsidian vault']);
        }
    }

    /**
     * Write note to Markdown file and commit to Git.
     */
    public function syncNoteToVault(Note $note): string
    {
        $this->initializeVault();

        $relativeFilePath = $note->getRelativeFilePath();
        $fullPath = $this->vaultPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeFilePath);

        // Ensure directory exists
        $dir = dirname($fullPath);
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        // Generate Obsidian Markdown content
        $markdownContent = $note->toObsidianMarkdown();

        // Write file
        File::put($fullPath, $markdownContent);

        // Git stage and commit
        $this->runGitCommand(['add', str_replace('\\', '/', $relativeFilePath)]);

        $spaceSlug = $note->space ? $note->space->slug : 'general';
        $commitMessage = sprintf(
            'docs(%s): %s [%s]',
            $spaceSlug,
            $note->title,
            $note->kb_id
        );

        $authorName = $note->owner ? $note->owner->name : 'Portal User';
        $authorEmail = $note->owner ? $note->owner->email : 'portal@local';
        $authorString = "{$authorName} <{$authorEmail}>";

        $this->runGitCommand([
            'commit',
            '-m',
            $commitMessage,
            "--author={$authorString}",
        ]);

        // Get latest commit hash
        $commitHash = trim($this->runGitCommand(['rev-parse', 'HEAD']));

        // Update note with hash and snapshot
        $note->updateQuietly([
            'git_commit_hash' => $commitHash,
            'published_content' => $note->content,
            'version' => $note->version + 1,
        ]);

        AuditLog::record('git.committed', $note, null, [
            'commit_hash' => $commitHash,
            'file_path' => $relativeFilePath,
        ]);

        return $commitHash;
    }

    /**
     * Execute a Git command inside the vault directory.
     */
    protected function runGitCommand(array $arguments): string
    {
        $process = new Process(array_merge(['git'], $arguments), $this->vaultPath);
        $process->run();

        if (! $process->isSuccessful()) {
            // Ignore error if nothing to commit
            if (str_contains($process->getErrorOutput(), 'nothing to commit') || str_contains($process->getOutput(), 'nothing to commit')) {
                return '';
            }
            throw new Exception('Git error: ' . $process->getErrorOutput() . ' ' . $process->getOutput());
        }

        return $process->getOutput();
    }
}
