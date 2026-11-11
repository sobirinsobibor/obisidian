<?php

namespace Database\Seeders;

use App\Enums\NoteStatus;
use App\Enums\NoteType;
use App\Enums\NoteVisibility;
use App\Enums\SpaceRole;
use App\Models\Note;
use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class KnowledgeBaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Platform Admin',
                'password' => Hash::make('password'),
                'is_global_admin' => true,
            ]
        );

        $spv = User::firstOrCreate(
            ['email' => 'spv.support@example.com'],
            [
                'name' => 'Supervisor Support',
                'password' => Hash::make('password'),
                'is_global_admin' => false,
            ]
        );

        $publisher = User::firstOrCreate(
            ['email' => 'publisher@example.com'],
            [
                'name' => 'Lead Tech Writer / Publisher',
                'password' => Hash::make('password'),
                'is_global_admin' => false,
            ]
        );

        $contributor = User::firstOrCreate(
            ['email' => 'contributor@example.com'],
            [
                'name' => 'John Contributor',
                'password' => Hash::make('password'),
                'is_global_admin' => false,
            ]
        );

        // 2. Create Spaces
        $spaceSupport = Space::firstOrCreate(
            ['slug' => 'support'],
            [
                'name' => 'Customer Support',
                'description' => 'SOP, Panduan penanganan tiket, dan FAQ untuk tim support.',
                'icon' => 'headset',
            ]
        );

        $spaceRnd = Space::firstOrCreate(
            ['slug' => 'rnd-internal'],
            [
                'name' => 'RnD & Engineering',
                'description' => 'Arsitektur sistem, runbook operasional server, dan RFC.',
                'icon' => 'code',
            ]
        );

        $spaceProduct = Space::firstOrCreate(
            ['slug' => 'produk-a'],
            [
                'name' => 'Produk A',
                'description' => 'Dokumentasi fitur dan spesifikasi rilis Produk A.',
                'icon' => 'box',
            ]
        );

        // 3. Assign Roles per Space
        $spaceSupport->users()->syncWithoutDetaching([
            $admin->id => ['role' => SpaceRole::ADMIN->value],
            $spv->id => ['role' => SpaceRole::REVIEWER->value],
            $publisher->id => ['role' => SpaceRole::PUBLISHER->value],
            $contributor->id => ['role' => SpaceRole::CONTRIBUTOR->value],
        ]);

        $spaceRnd->users()->syncWithoutDetaching([
            $admin->id => ['role' => SpaceRole::ADMIN->value],
            $contributor->id => ['role' => SpaceRole::CONTRIBUTOR->value],
        ]);

        $spaceProduct->users()->syncWithoutDetaching([
            $admin->id => ['role' => SpaceRole::ADMIN->value],
            $publisher->id => ['role' => SpaceRole::PUBLISHER->value],
        ]);

        // 4. Create Initial Sample Note
        Note::firstOrCreate(
            ['slug' => 'cara-reset-password-admin'],
            [
                'kb_id' => 'kb-000001',
                'title' => 'Cara Reset Password Pengguna Melalui Admin Portal',
                'space_id' => $spaceSupport->id,
                'type' => NoteType::RUNBOOK,
                'product' => 'portal-core',
                'visibility' => NoteVisibility::INTERNAL,
                'status' => NoteStatus::DRAFT,
                'owner_id' => $contributor->id,
                'tags' => ['akun', 'keamanan', 'support-tier1'],
                'content' => <<<MARKDOWN
                    # Cara Reset Password Pengguna Melalui Admin Portal

                    Dokumen runbook ini memandu agen support saat menangani permintaan reset akun pengguna.

                    > [!NOTE]
                    > Pastikan identitas pengguna sudah terverifikasi lewat email atau telepon terdaftar sebelum memicu link reset.

                    ## Langkah Penanganan
                    1. Buka halaman admin pengguna.
                    2. Cari berdasarkan ID atau email pengguna.
                    3. Tekan tombol **Reset Kata Sandi** untuk mengirimkan tautan baru.
                    4. Pastikan tiket di helpdesk diperbarui sesuai nomor referensi [[kb-000001]].
                MARKDOWN
            ]
        );
    }
}
