<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { renderObsidianMarkdown } from '@/Utils/markdown';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    review: Object,
    note: Object,
    isSelf: Boolean,
    comments: Array,
});

const approveForm = useForm({
    notes: '',
});

const changesForm = useForm({
    notes: '',
});

const commentForm = useForm({
    comment: '',
    line_number: null,
});

const activeAction = ref('approve'); // 'approve', 'changes', or 'comment'

const renderedHtml = computed(() => {
    return renderObsidianMarkdown(props.note.content);
});

function handleApprove() {
    approveForm.post(route('reviews.approve', props.review.id));
}

function handleRequestChanges() {
    changesForm.post(route('reviews.request-changes', props.review.id));
}

function handleComment() {
    commentForm.post(route('reviews.comments.store', props.review.id), {
        onSuccess: () => {
            commentForm.reset();
        },
    });
}
</script>

<template>
    <Head :title="`Review: ${note.title}`" />

    <AuthenticatedLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Header & Navigation -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <Link :href="route('reviews.index')" class="hover:text-indigo-600">Antrean Review</Link>
                    <span>/</span>
                    <span class="font-mono text-purple-700 font-bold">{{ note.kb_id }}</span>
                    <span>/</span>
                    <span>Lembar Kerja Evaluasi</span>
                </div>
            </div>

            <!-- Anti Self-Approval Banner (PRD Rule 4.2.a) -->
            <div v-if="isSelf" class="p-4 rounded-2xl bg-amber-50 border border-amber-300 text-amber-900 text-xs flex items-start gap-3">
                <span class="text-xl">⚠️</span>
                <div>
                    <div class="font-bold text-sm text-amber-950">Aturan Tata Kelola: Anti Self-Approval (PRD 4.2.a)</div>
                    <p class="mt-0.5 text-amber-800">
                        Anda adalah penulis catatan ini. Anda tidak diizinkan menyetujui kontribusi Anda sendiri demi menjaga akurasi dan kepatuhan. Evaluasi ini harus dilakukan oleh Reviewer / Supervisor lain.
                    </p>
                </div>
            </div>

            <!-- Review Stage Info Bar -->
            <div class="bg-indigo-900 text-white rounded-2xl p-5 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-indigo-800 text-indigo-200 text-xs font-semibold mb-2">
                        <span>📋</span>
                        {{ review.stage === 'internal' ? 'Tahap 1: Evaluasi Internal (Akurasi Teknis)' : 'Tahap 2: Evaluasi Publik (Kebijakan & Branding)' }}
                    </div>
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight">
                        {{ note.title }}
                    </h1>
                    <div class="flex items-center gap-3 text-xs text-indigo-200 mt-2">
                        <span>Penulis: <strong>{{ note.owner?.name }}</strong></span>
                        <span>•</span>
                        <span>Space: <strong>{{ note.space?.name }}</strong></span>
                        <span>•</span>
                        <span>Tipe: <strong>{{ note.type }}</strong></span>
                        <span>•</span>
                        <span>Visibilitas: <strong class="capitalize">{{ note.visibility }}</strong></span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('notes.show', note.id)"
                        class="px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-medium text-white transition"
                    >
                        Buka Halaman Catatan ↗
                    </Link>
                </div>
            </div>

            <!-- Workspace Layout (Document + Evaluation Action Panel) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left: Rendered Markdown Content -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white border border-slate-200 rounded-2xl p-8 shadow-xs">
                        <div class="border-b border-slate-100 pb-4 mb-6 flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Pratinjau Dokumen Obsidian Markdown
                            </span>
                            <span class="text-xs text-slate-400 font-mono">
                                {{ note.slug }}.md
                            </span>
                        </div>

                        <div class="prose prose-slate max-w-none text-sm" v-html="renderedHtml"></div>
                    </div>

                    <!-- Discussion & Feedback Comments -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-4">
                        <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">
                            Diskusi & Komentar Review ({{ comments.length }})
                        </h3>

                        <div class="space-y-3">
                            <div
                                v-for="c in comments"
                                :key="c.id"
                                class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 space-y-1.5"
                            >
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-slate-800">{{ c.user?.name }}</span>
                                    <span class="text-slate-400 text-[11px]">{{ new Date(c.created_at).toLocaleString('id-ID') }}</span>
                                </div>
                                <div v-if="c.line_number" class="text-[11px] font-mono text-purple-600 bg-purple-50 px-1.5 py-0.5 rounded inline-block">
                                    Baris {{ c.line_number }}
                                </div>
                                <p class="text-xs text-slate-700 leading-relaxed">{{ c.comment }}</p>
                            </div>

                            <div v-if="comments.length === 0" class="text-xs text-slate-400 py-3 text-center">
                                Belum ada komentar pada dokumen ini.
                            </div>
                        </div>

                        <!-- Add Comment Box -->
                        <form @submit.prevent="handleComment" class="pt-3 border-t border-slate-100 space-y-2">
                            <textarea
                                v-model="commentForm.comment"
                                rows="3"
                                placeholder="Tulis masukan atau pertanyaan untuk penulis..."
                                class="w-full text-xs rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="commentForm.processing || !commentForm.comment"
                                    class="px-3.5 py-1.5 text-xs font-semibold text-white bg-slate-800 hover:bg-slate-900 rounded-lg transition disabled:opacity-50"
                                >
                                    Kirim Komentar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right: Decision Card -->
                <div class="space-y-6">
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-5">
                        <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">
                            Keputusan Review
                        </h2>

                        <!-- Action Tabs -->
                        <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-xl text-xs font-semibold">
                            <button
                                type="button"
                                @click="activeAction = 'approve'"
                                :class="[activeAction === 'approve' ? 'bg-white text-emerald-700 shadow-xs' : 'text-slate-600 hover:text-slate-900']"
                                class="py-2 rounded-lg transition text-center"
                            >
                                ✓ Setujui
                            </button>
                            <button
                                type="button"
                                @click="activeAction = 'changes'"
                                :class="[activeAction === 'changes' ? 'bg-white text-rose-700 shadow-xs' : 'text-slate-600 hover:text-slate-900']"
                                class="py-2 rounded-lg transition text-center"
                            >
                                ↺ Minta Revisi
                            </button>
                        </div>

                        <!-- Approve Form -->
                        <div v-show="activeAction === 'approve'" class="space-y-4">
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Dokumen ini akan disetujui.
                                <span v-if="note.visibility === 'public' && review.stage === 'internal'">
                                    Karena dokumen bersifat publik, persetujuan ini akan mengalihkan catatan ke <strong>Tahap 2 (Review Publik)</strong>.
                                </span>
                                <span v-else>
                                    Catatan akan segera diterbitkan dan <strong>disinkronkan via Git ke Obsidian Vault fisik</strong> secara otomatis.
                                </span>
                            </p>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Persetujuan (Opsional)</label>
                                <textarea
                                    v-model="approveForm.notes"
                                    rows="3"
                                    placeholder="Contoh: Dokumen telah sesuai SOP dan dapat dipublikasikan."
                                    class="w-full text-xs rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                                ></textarea>
                            </div>

                            <button
                                type="button"
                                :disabled="isSelf || approveForm.processing"
                                @click="handleApprove"
                                class="w-full py-2.5 px-4 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-1.5"
                            >
                                <span>✓</span>
                                Setujui & Publikasikan
                            </button>
                        </div>

                        <!-- Request Changes Form -->
                        <div v-show="activeAction === 'changes'" class="space-y-4">
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Catatan akan dikembalikan ke status <strong>Perlu Revisi (Changes Requested)</strong> agar diperbaiki oleh penulis.
                            </p>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Poin Revisi yang Diminta <span class="text-rose-500">*</span>
                                </label>
                                <textarea
                                    v-model="changesForm.notes"
                                    rows="4"
                                    placeholder="Jelaskan bagian mana yang perlu diperbaiki atau dilengkapi oleh penulis..."
                                    class="w-full text-xs rounded-xl border-slate-300 focus:border-rose-500 focus:ring-rose-500"
                                ></textarea>
                                <div v-if="changesForm.errors.notes" class="text-xs text-rose-500 mt-1 font-medium">
                                    {{ changesForm.errors.notes }}
                                </div>
                            </div>

                            <button
                                type="button"
                                :disabled="isSelf || changesForm.processing || !changesForm.notes"
                                @click="handleRequestChanges"
                                class="w-full py-2.5 px-4 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-xs transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-1.5"
                            >
                                <span>↺</span>
                                Kirim Permintaan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
