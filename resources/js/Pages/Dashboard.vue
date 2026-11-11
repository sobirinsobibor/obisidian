<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    spaces: Array,
    myRecentNotes: Array,
    pendingReviews: Array,
    recentPublished: Array,
    canReview: Boolean,
});

function getStatusBadge(status) {
    const map = {
        draft: { label: 'Draft', class: 'bg-slate-100 text-slate-700 border-slate-200' },
        in_review: { label: 'Review Internal', class: 'bg-amber-100 text-amber-800 border-amber-200' },
        in_public_review: { label: 'Review Publik', class: 'bg-blue-100 text-blue-800 border-blue-200' },
        published_internal: { label: 'Terbit Internal', class: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
        published_public: { label: 'Terbit Publik', class: 'bg-indigo-100 text-indigo-800 border-indigo-200' },
        changes_requested: { label: 'Perlu Revisi', class: 'bg-rose-100 text-rose-800 border-rose-200' },
        needs_review: { label: 'Review Berkala', class: 'bg-orange-100 text-orange-800 border-orange-200' },
        archived: { label: 'Arsip', class: 'bg-gray-100 text-gray-500 border-gray-200' },
    };
    return map[status] || { label: status, class: 'bg-slate-100 text-slate-700 border-slate-200' };
}
</script>

<template>
    <Head title="Dashboard Knowledge Base" />

    <AuthenticatedLayout>
        <!-- Header Banner -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Portal Knowledge Base
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Satu pusat pengetahuan terkelola, sinkron otomatis ke Obsidian Vault & dokumentasi.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('notes.create')"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tulis Dokumen Baru
                </Link>
            </div>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
                <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Catatan</div>
                <div class="mt-2 text-3xl font-extrabold text-slate-900">{{ stats.total_notes }}</div>
                <div class="mt-1 text-xs text-slate-400">Di seluruh space organisasi</div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
                <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Sudah Terbit</div>
                <div class="mt-2 text-3xl font-extrabold text-emerald-600">{{ stats.published_notes }}</div>
                <div class="mt-1 text-xs text-slate-400">Sinkron di Obsidian Vault</div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
                <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Menunggu Review</div>
                <div class="mt-2 text-3xl font-extrabold text-amber-500">{{ stats.pending_review }}</div>
                <div class="mt-1 text-xs text-slate-400">Dalam antrean persetujuan</div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
                <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Draft Saya</div>
                <div class="mt-2 text-3xl font-extrabold text-indigo-600">{{ stats.my_drafts }}</div>
                <div class="mt-1 text-xs text-slate-400">Perlu diselesaikan / direvisi</div>
            </div>
        </div>

        <!-- Main Section: Spaces & Queue -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left: Spaces & Published -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Spaces Overview -->
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Spaces / Kategori Divisi</h2>
                            <p class="text-xs text-slate-500">Folder utama Obsidian Vault untuk setiap domain</p>
                        </div>
                        <Link :href="route('spaces.index')" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            Lihat Semua →
                        </Link>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <Link
                            v-for="space in spaces"
                            :key="space.id"
                            :href="route('spaces.show', space.slug)"
                            class="p-4 rounded-xl border border-slate-100 bg-slate-50/70 hover:bg-slate-100/80 hover:border-indigo-200 transition group"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xl">📁</span>
                                <span class="text-xs font-mono font-medium px-2 py-0.5 rounded bg-white border border-slate-200 text-slate-600">
                                    {{ space.notes_count }} note
                                </span>
                            </div>
                            <h3 class="text-sm font-semibold text-slate-800 group-hover:text-indigo-600 transition">
                                {{ space.name }}
                            </h3>
                            <p class="text-xs text-slate-500 line-clamp-2 mt-1">
                                {{ space.description || 'Tidak ada deskripsi' }}
                            </p>
                            <div v-if="space.user_role_label" class="mt-3 text-[11px] font-medium text-indigo-600">
                                Peran: {{ space.user_role_label }}
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Recent Published Notes -->
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Catatan Baru Terbit</h2>
                            <p class="text-xs text-slate-500">Artikel dan SOP yang siap digunakan tim</p>
                        </div>
                        <Link :href="route('notes.index')" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            Buka Explorer →
                        </Link>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <div
                            v-for="note in recentPublished"
                            :key="note.id"
                            class="py-3.5 flex items-center justify-between hover:bg-slate-50/50 px-2 rounded-lg transition"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-mono font-semibold text-purple-600 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200">
                                        {{ note.kb_id }}
                                    </span>
                                    <Link :href="route('notes.show', note.id)" class="text-sm font-semibold text-slate-800 hover:text-indigo-600 transition">
                                        {{ note.title }}
                                    </Link>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-slate-400">
                                    <span>Space: {{ note.space?.name }}</span>
                                    <span>•</span>
                                    <span>Penulis: {{ note.owner?.name }}</span>
                                </div>
                            </div>
                            <span :class="['text-xs font-medium px-2 py-0.5 rounded-full border', getStatusBadge(note.status).class]">
                                {{ getStatusBadge(note.status).label }}
                            </span>
                        </div>
                        <div v-if="recentPublished.length === 0" class="py-6 text-center text-xs text-slate-400">
                            Belum ada catatan yang diterbitkan.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Review Queue & My Drafts -->
            <div class="space-y-8">
                <!-- Review Queue (if reviewer) -->
                <div v-if="canReview" class="bg-amber-50/50 border border-amber-200 rounded-2xl p-6 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="flex h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                            <h2 class="text-base font-bold text-amber-950">Antrean Review Anda</h2>
                        </div>
                        <Link :href="route('reviews.index')" class="text-xs font-semibold text-amber-700 hover:text-amber-900">
                            Semua ({{ pendingReviews.length }}) →
                        </Link>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="rev in pendingReviews"
                            :key="rev.id"
                            class="bg-white border border-amber-100 rounded-xl p-3.5 shadow-xs"
                        >
                            <div class="text-xs font-mono font-semibold text-slate-400 mb-1">
                                {{ rev.note?.kb_id }} • Tahap {{ rev.stage === 'internal' ? 'Review Internal' : 'Review Publik' }}
                            </div>
                            <Link :href="route('reviews.show', rev.id)" class="text-sm font-semibold text-slate-900 hover:text-indigo-600 line-clamp-1">
                                {{ rev.note?.title }}
                            </Link>
                            <div class="mt-2 flex items-center justify-between text-xs text-slate-500">
                                <span>Oleh {{ rev.note?.owner?.name }}</span>
                                <Link
                                    :href="route('reviews.show', rev.id)"
                                    class="text-xs font-medium text-amber-700 bg-amber-50 hover:bg-amber-100 px-2 py-1 rounded-md transition"
                                >
                                    Periksa →
                                </Link>
                            </div>
                        </div>
                        <div v-if="pendingReviews.length === 0" class="text-center py-4 text-xs text-slate-400">
                            Tidak ada antrean review yang tertunda.
                        </div>
                    </div>
                </div>

                <!-- My Drafts & Active Work -->
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs">
                    <h2 class="text-base font-bold text-slate-900 mb-4">Catatan Saya Terkini</h2>
                    <div class="divide-y divide-slate-100">
                        <div
                            v-for="note in myRecentNotes"
                            :key="note.id"
                            class="py-3 flex items-center justify-between"
                        >
                            <div class="space-y-0.5">
                                <Link :href="route('notes.show', note.id)" class="text-xs font-semibold text-slate-800 hover:text-indigo-600 line-clamp-1">
                                    {{ note.title }}
                                </Link>
                                <div class="text-[11px] text-slate-400">
                                    {{ note.space?.name }} • {{ note.type }}
                                </div>
                            </div>
                            <span :class="['text-[11px] font-medium px-2 py-0.5 rounded-full border', getStatusBadge(note.status).class]">
                                {{ getStatusBadge(note.status).label }}
                            </span>
                        </div>
                        <div v-if="myRecentNotes.length === 0" class="text-center py-4 text-xs text-slate-400">
                            Anda belum memiliki catatan. Mulai tulis dengan tombol di atas!
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
