<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

const props = defineProps({
    notes: Object,
    spaces: Array,
    types: Array,
    visibilities: Array,
    statuses: Array,
    filters: Object,
});

const filterForm = reactive({
    search: props.filters.search || '',
    space: props.filters.space || '',
    type: props.filters.type || '',
    visibility: props.filters.visibility || '',
    status: props.filters.status || '',
});

function applyFilters() {
    router.get(route('notes.index'), filterForm, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    filterForm.search = '';
    filterForm.space = '';
    filterForm.type = '';
    filterForm.visibility = '';
    filterForm.status = '';
    applyFilters();
}

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
    <Head title="Eksplorasi Dokumen Knowledge Base" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight sm:text-3xl">
                        Eksplorasi Dokumen
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Daftar seluruh catatan, panduan, dan runbook yang tersinkron dengan format Obsidian.
                    </p>
                </div>
                <Link
                    :href="route('notes.create')"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tulis Catatan Baru
                </Link>
            </div>

            <!-- Search & Filters Bar (PRD FR-12) -->
            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                    <!-- Search Input -->
                    <div class="md:col-span-2 relative">
                        <input
                            type="text"
                            v-model="filterForm.search"
                            @keydown.enter="applyFilters"
                            placeholder="Cari judul, isi teks, atau nomor KB..."
                            class="w-full text-xs rounded-lg border-slate-300 pl-8 focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <span class="absolute left-2.5 top-2.5 text-slate-400 text-xs">🔍</span>
                    </div>

                    <!-- Space Filter -->
                    <div>
                        <select
                            v-model="filterForm.space"
                            @change="applyFilters"
                            class="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white"
                        >
                            <option value="">Semua Space</option>
                            <option v-for="s in spaces" :key="s.id" :value="s.slug">{{ s.name }}</option>
                        </select>
                    </div>

                    <!-- Type Filter -->
                    <div>
                        <select
                            v-model="filterForm.type"
                            @change="applyFilters"
                            class="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white"
                        >
                            <option value="">Semua Tipe</option>
                            <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <select
                            v-model="filterForm.status"
                            @change="applyFilters"
                            class="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white"
                        >
                            <option value="">Semua Status</option>
                            <option v-for="st in statuses" :key="st.value" :value="st.value">{{ st.label }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-slate-500 pt-1">
                    <div>
                        Menampilkan total <strong>{{ notes.total }}</strong> catatan
                    </div>
                    <button
                        type="button"
                        @click="resetFilters"
                        class="text-indigo-600 hover:text-indigo-800 font-medium"
                    >
                        Reset Filter
                    </button>
                </div>
            </div>

            <!-- Notes Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="note in notes.data"
                    :key="note.id"
                    class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs hover:border-indigo-300 hover:shadow-sm transition flex flex-col justify-between"
                >
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded border border-purple-200">
                                {{ note.kb_id }}
                            </span>
                            <span :class="['text-[11px] font-medium px-2 py-0.5 rounded-full border', getStatusBadge(note.status).class]">
                                {{ getStatusBadge(note.status).label }}
                            </span>
                        </div>

                        <Link :href="route('notes.show', note.id)" class="block">
                            <h3 class="text-base font-bold text-slate-900 hover:text-indigo-600 transition line-clamp-2">
                                {{ note.title }}
                            </h3>
                        </Link>

                        <div class="flex flex-wrap gap-1 text-[11px]">
                            <span
                                v-for="tag in (note.tags || []).slice(0, 3)"
                                :key="tag"
                                class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600"
                            >
                                #{{ tag }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                        <span>Space: <strong class="text-slate-600">{{ note.space?.name }}</strong></span>
                        <span>{{ note.owner?.name }}</span>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="notes.data.length === 0" class="bg-white border border-slate-200 rounded-2xl p-12 text-center">
                <div class="text-4xl mb-3">📄</div>
                <h3 class="text-base font-bold text-slate-900 mb-1">Tidak ada catatan ditemukan</h3>
                <p class="text-xs text-slate-500 mb-4">Coba sesuaikan kata kunci pencarian atau filter Anda.</p>
                <Link
                    :href="route('notes.create')"
                    class="inline-flex items-center gap-1 px-4 py-2 text-xs font-semibold bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition"
                >
                    + Buat Catatan Pertama
                </Link>
            </div>

            <!-- Pagination Links -->
            <div v-if="notes.links && notes.links.length > 3" class="flex justify-center gap-1 pt-4">
                <Link
                    v-for="(link, i) in notes.links"
                    :key="i"
                    :href="link.url || '#'"
                    :class="[
                        link.active ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200',
                        !link.url ? 'opacity-40 cursor-not-allowed' : ''
                    ]"
                    class="px-3 py-1.5 text-xs font-medium rounded-lg transition"
                    v-html="link.label"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
