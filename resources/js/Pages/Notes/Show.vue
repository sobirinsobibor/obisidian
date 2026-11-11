<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { renderObsidianMarkdown } from '@/Utils/markdown';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    note: Object,
    canEdit: Boolean,
    canReview: Boolean,
    userRole: String,
    auditLogs: Array,
});

const submitReviewForm = useForm({});
const showFrontmatter = ref(false);

const renderedHtml = computed(() => {
    return renderObsidianMarkdown(props.note.content);
});

function getStatusBadge(status) {
    const map = {
        draft: { label: 'Draft', class: 'bg-slate-100 text-slate-700 border-slate-200' },
        in_review: { label: 'Menunggu Review Internal', class: 'bg-amber-100 text-amber-800 border-amber-200' },
        in_public_review: { label: 'Menunggu Review Publik', class: 'bg-blue-100 text-blue-800 border-blue-200' },
        published_internal: { label: 'Terbit Internal', class: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
        published_public: { label: 'Terbit Publik', class: 'bg-indigo-100 text-indigo-800 border-indigo-200' },
        changes_requested: { label: 'Perlu Revisi', class: 'bg-rose-100 text-rose-800 border-rose-200' },
        needs_review: { label: 'Perlu Review Berkala', class: 'bg-orange-100 text-orange-800 border-orange-200' },
        archived: { label: 'Diarsipkan', class: 'bg-gray-100 text-gray-500 border-gray-200' },
    };
    return map[status] || { label: status, class: 'bg-slate-100 text-slate-700 border-slate-200' };
}

function submitForReview() {
    if (confirm('Ajukan catatan ini untuk peninjauan (review)?')) {
        submitReviewForm.post(route('notes.submit-review', props.note.id));
    }
}
</script>

<template>
    <Head :title="note.title" />

    <AuthenticatedLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Breadcrumbs & Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <Link :href="route('notes.index')" class="hover:text-indigo-600">Catatan</Link>
                    <span>/</span>
                    <Link :href="route('spaces.show', note.space.slug)" class="hover:text-indigo-600 font-medium">
                        {{ note.space.name }}
                    </Link>
                    <span>/</span>
                    <span class="font-mono text-purple-600 font-semibold">{{ note.kb_id }}</span>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        v-if="canEdit && (note.status === 'draft' || note.status === 'changes_requested')"
                        :href="route('notes.edit', note.id)"
                        class="px-3.5 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition shadow-xs flex items-center gap-1.5"
                    >
                        <span>✏️</span> Edit Catatan
                    </Link>

                    <button
                        v-if="canEdit && (note.status === 'draft' || note.status === 'changes_requested')"
                        @click="submitForReview"
                        :disabled="submitReviewForm.processing"
                        class="px-3.5 py-1.5 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition shadow-xs flex items-center gap-1.5"
                    >
                        <span>🚀</span> Ajukan Review
                    </button>

                    <button
                        type="button"
                        @click="showFrontmatter = !showFrontmatter"
                        class="px-3 py-1.5 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition"
                    >
                        {{ showFrontmatter ? 'Sembunyikan Frontmatter' : 'Lihat Frontmatter Obsidian' }}
                    </button>
                </div>
            </div>

            <!-- Frontmatter Inspector Panel -->
            <div v-if="showFrontmatter" class="bg-slate-900 rounded-xl p-4 text-xs font-mono text-slate-200 shadow-sm border border-slate-800">
                <div class="text-[11px] text-slate-400 uppercase tracking-wider mb-2 font-semibold">
                    Simulasi Berkas Obsidian Vault: /spaces/{{ note.space?.slug }}/{{ note.type }}/{{ note.slug }}.md
                </div>
                <pre class="overflow-x-auto text-emerald-400">---
id: {{ note.kb_id }}
title: "{{ note.title }}"
space: {{ note.space?.slug }}
type: {{ note.type }}
product: {{ note.product }}
visibility: {{ note.visibility }}
status: {{ note.status }}
owner: {{ note.owner?.email }}
reviewer: {{ note.reviewer?.email || '~' }}
last_reviewed: {{ note.last_reviewed_at || '~' }}
review_due: {{ note.review_due_at || '~' }}
tags: [{{ (note.tags || []).join(', ') }}]
---</pre>
            </div>

            <!-- Main Content Layout (Article + Sidebar) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left: Note Document Rendered -->
                <div class="lg:col-span-2 space-y-6">
                    <article class="bg-white border border-slate-200 rounded-2xl p-8 shadow-xs">
                        <!-- Article Header -->
                        <div class="border-b border-slate-100 pb-5 mb-6">
                            <div class="flex items-center gap-2 mb-2">
                                <span :class="['text-xs font-medium px-2.5 py-0.5 rounded-full border', getStatusBadge(note.status).class]">
                                    {{ getStatusBadge(note.status).label }}
                                </span>
                                <span class="text-xs font-mono text-slate-500 uppercase">
                                    {{ note.type }}
                                </span>
                                <span class="text-slate-300">•</span>
                                <span class="text-xs text-slate-500">
                                    Visibilitas: <strong class="capitalize">{{ note.visibility }}</strong>
                                </span>
                            </div>

                            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                                {{ note.title }}
                            </h1>

                            <div class="flex flex-wrap items-center gap-2 mt-4 text-xs">
                                <span
                                    v-for="tag in note.tags || []"
                                    :key="tag"
                                    class="px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-200 font-medium"
                                >
                                    #{{ tag }}
                                </span>
                            </div>
                        </div>

                        <!-- Rendered Markdown Body -->
                        <div class="prose prose-slate max-w-none text-sm text-slate-800" v-html="renderedHtml"></div>
                    </article>

                    <!-- Review History & Feedback Notes -->
                    <div v-if="note.reviews && note.reviews.length > 0" class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-4">
                        <h3 class="text-sm font-bold text-slate-900">Riwayat Sesi Review</h3>
                        <div class="divide-y divide-slate-100">
                            <div v-for="rev in note.reviews" :key="rev.id" class="py-3 text-xs space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-slate-800">
                                        Tahap: {{ rev.stage === 'internal' ? 'Review Internal' : 'Review Publik' }}
                                    </span>
                                    <span class="font-mono text-slate-500 uppercase">{{ rev.status }}</span>
                                </div>
                                <div class="text-slate-500">
                                    Diajukan oleh: {{ rev.requester?.name }}
                                    <span v-if="rev.reviewer">• Direview oleh: {{ rev.reviewer?.name }}</span>
                                </div>
                                <div v-if="rev.resolution_notes" class="p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 mt-2">
                                    <strong>Catatan Reviewer:</strong> {{ rev.resolution_notes }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Metadata & Audit Sidebar -->
                <div class="space-y-6">
                    <!-- Document Metadata Box -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-3.5 text-xs">
                        <h3 class="font-bold text-slate-900 text-sm border-b border-slate-100 pb-2">Informasi Berkas</h3>

                        <div class="flex justify-between py-1">
                            <span class="text-slate-500">Nomor KB:</span>
                            <span class="font-mono font-bold text-purple-700">{{ note.kb_id }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-slate-500">Space:</span>
                            <span class="font-semibold text-slate-800">{{ note.space?.name }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-slate-500">Penulis:</span>
                            <span class="font-medium text-slate-800">{{ note.owner?.name }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-slate-500">Reviewer:</span>
                            <span class="text-slate-800">{{ note.reviewer?.name || 'Belum ditugaskan' }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-slate-500">Review Jatuh Tempo:</span>
                            <span class="text-slate-800">{{ note.review_due_at || '-' }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-slate-500">Versi Terbit:</span>
                            <span class="font-mono text-slate-800">v{{ note.version }}</span>
                        </div>
                        <div v-if="note.git_commit_hash" class="pt-2 border-t border-slate-100">
                            <span class="text-slate-500 block mb-1">Git Commit Vault:</span>
                            <code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px] block truncate">
                                {{ note.git_commit_hash }}
                            </code>
                        </div>
                    </div>

                    <!-- Audit Trail (FR-13) -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-3 text-xs">
                        <h3 class="font-bold text-slate-900 text-sm border-b border-slate-100 pb-2">Jejak Audit (Audit Log)</h3>
                        <div class="space-y-3">
                            <div v-for="log in auditLogs" :key="log.id" class="border-l-2 border-slate-200 pl-3 py-0.5">
                                <div class="font-semibold text-slate-800 font-mono text-[11px]">{{ log.action }}</div>
                                <div class="text-slate-400 text-[10px]">{{ new Date(log.created_at).toLocaleString('id-ID') }}</div>
                                <div class="text-slate-500 text-[11px]">Oleh: {{ log.user ? log.user.name : 'Sistem Portal' }}</div>
                            </div>
                            <div v-if="auditLogs.length === 0" class="text-slate-400 italic">
                                Belum ada catatan log aktivitas.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
