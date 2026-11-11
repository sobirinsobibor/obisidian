<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    space: Object,
    notes: Object,
    userRole: String,
    userRoleLabel: String,
    members: Array,
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
    <Head :title="space.name" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Space Banner -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-mono font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded border border-purple-200">
                            /spaces/{{ space.slug }}
                        </span>
                        <span v-if="userRoleLabel" class="text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                            🛡️ {{ userRoleLabel }}
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                        {{ space.name }}
                    </h1>

                    <p class="text-xs sm:text-sm text-slate-500 max-w-2xl leading-relaxed">
                        {{ space.description || 'Tidak ada deskripsi untuk space ini.' }}
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        :href="route('notes.create', { space: space.slug })"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm transition"
                    >
                        <span>+</span> Tulis Dokumen di Sini
                    </Link>
                </div>
            </div>

            <!-- Notes Inside Space -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Notes List -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h2 class="text-base font-bold text-slate-900">
                                Berkas & Catatan ({{ notes.total }})
                            </h2>
                        </div>

                        <div class="divide-y divide-slate-100">
                            <div
                                v-for="note in notes.data"
                                :key="note.id"
                                class="py-3.5 flex items-center justify-between hover:bg-slate-50/50 px-2 rounded-lg transition"
                            >
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-mono font-bold text-purple-600 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200">
                                            {{ note.kb_id }}
                                        </span>
                                        <Link :href="route('notes.show', note.id)" class="text-sm font-semibold text-slate-900 hover:text-indigo-600 transition">
                                            {{ note.title }}
                                        </Link>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs text-slate-400">
                                        <span class="capitalize">Tipe: {{ note.type }}</span>
                                        <span>•</span>
                                        <span>Penulis: {{ note.owner?.name }}</span>
                                        <span>•</span>
                                        <span>Diperbarui: {{ new Date(note.updated_at).toLocaleDateString('id-ID') }}</span>
                                    </div>
                                </div>

                                <span :class="['text-xs font-medium px-2 py-0.5 rounded-full border', getStatusBadge(note.status).class]">
                                    {{ getStatusBadge(note.status).label }}
                                </span>
                            </div>

                            <div v-if="notes.data.length === 0" class="py-10 text-center text-slate-400 text-xs">
                                Belum ada catatan dalam space ini.
                            </div>
                        </div>

                        <!-- Pagination -->
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
                </div>

                <!-- Right: Space Members -->
                <div>
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-4">
                        <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">
                            Anggota & Hak Akses (RBAC)
                        </h3>

                        <div class="divide-y divide-slate-100">
                            <div
                                v-for="member in members"
                                :key="member.id"
                                class="py-2.5 flex items-center justify-between text-xs"
                            >
                                <div>
                                    <div class="font-semibold text-slate-800">{{ member.name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ member.email }}</div>
                                </div>
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-mono text-[10px] uppercase font-semibold">
                                    {{ member.pivot?.role }}
                                </span>
                            </div>

                            <div v-if="members.length === 0" class="py-3 text-xs text-slate-400 text-center">
                                Belum ada anggota yang ditugaskan khusus.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
