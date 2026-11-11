<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    reviews: Object,
    history: Array,
});
</script>

<template>
    <Head title="Antrean Review Dokumen" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight sm:text-3xl">
                        Antrean Peninjauan (Review)
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Gerbang tata kelola dokumen sebelum dipublikasikan ke Obsidian Vault atau situs publik.
                    </p>
                </div>
            </div>

            <!-- Pending Reviews Section -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        Menunggu Persetujuan Anda ({{ reviews.total }})
                    </h2>
                </div>

                <div class="divide-y divide-slate-100">
                    <div
                        v-for="rev in reviews.data"
                        :key="rev.id"
                        class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50/60 px-3 rounded-xl transition"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded border border-purple-200">
                                    {{ rev.note?.kb_id }}
                                </span>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200">
                                    {{ rev.stage === 'internal' ? 'Tahap 1: Review Internal' : 'Tahap 2: Review Publik' }}
                                </span>
                                <span class="text-xs text-slate-400">• Space: {{ rev.note?.space?.name }}</span>
                            </div>
                            <Link :href="route('reviews.show', rev.id)" class="text-base font-semibold text-slate-900 hover:text-indigo-600 transition block">
                                {{ rev.note?.title }}
                            </Link>
                            <div class="text-xs text-slate-500">
                                Penulis: <strong class="text-slate-700">{{ rev.note?.owner?.name }}</strong> •
                                Diajukan: {{ new Date(rev.created_at).toLocaleDateString('id-ID') }}
                            </div>
                        </div>

                        <div>
                            <Link
                                :href="route('reviews.show', rev.id)"
                                class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-xs transition"
                            >
                                Buka Lembar Review →
                            </Link>
                        </div>
                    </div>

                    <div v-if="reviews.data.length === 0" class="py-12 text-center text-slate-400">
                        <div class="text-3xl mb-2">🎉</div>
                        <div class="text-sm font-semibold text-slate-700">Semua review telah selesai!</div>
                        <div class="text-xs mt-1">Tidak ada dokumen yang sedang menunggu peninjauan Anda saat ini.</div>
                    </div>
                </div>
            </div>

            <!-- History Section -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-4">
                <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">
                    Riwayat Keputusan Review Terakhir
                </h3>

                <div class="divide-y divide-slate-100">
                    <div
                        v-for="h in history"
                        :key="h.id"
                        class="py-3 flex items-center justify-between text-xs"
                    >
                        <div class="space-y-0.5">
                            <span class="font-semibold text-slate-800">{{ h.note?.title }}</span>
                            <div class="text-slate-400">
                                {{ h.note?.space?.name }} • Reviewer: {{ h.reviewer?.name || 'Sistem' }} •
                                {{ new Date(h.resolved_at).toLocaleDateString('id-ID') }}
                            </div>
                        </div>

                        <span
                            :class="[
                                h.status === 'approved' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'
                            ]"
                            class="px-2.5 py-1 rounded-full font-semibold border uppercase text-[10px]"
                        >
                            {{ h.status === 'approved' ? 'Disetujui' : 'Diminta Revisi' }}
                        </span>
                    </div>

                    <div v-if="history.length === 0" class="py-4 text-center text-xs text-slate-400">
                        Belum ada riwayat peninjauan sebelumnya.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
