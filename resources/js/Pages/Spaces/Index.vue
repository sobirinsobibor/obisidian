<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    spaces: Array,
});
</script>

<template>
    <Head title="Spaces Knowledge Base" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight sm:text-3xl">
                        Spaces / Direktori Divisi
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Pengelompokan pengetahuan berdasarkan divisi atau domain produk, masing-masing memiliki folder khusus di repositori Obsidian Vault.
                    </p>
                </div>
            </div>

            <!-- Spaces Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="space in spaces"
                    :key="space.id"
                    class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs flex flex-col justify-between hover:border-indigo-300 transition group"
                >
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-2xl">📁</span>
                            <span class="text-xs font-mono font-medium px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700">
                                {{ space.notes_count }} catatan
                            </span>
                        </div>

                        <div>
                            <Link :href="route('spaces.show', space.slug)" class="block">
                                <h3 class="text-lg font-bold text-slate-900 group-hover:text-indigo-600 transition">
                                    {{ space.name }}
                                </h3>
                            </Link>
                            <span class="text-xs font-mono text-purple-600 font-semibold">
                                /spaces/{{ space.slug }}
                            </span>
                        </div>

                        <p class="text-xs text-slate-500 leading-relaxed">
                            {{ space.description || 'Tidak ada deskripsi.' }}
                        </p>

                        <!-- User Role Badge if member -->
                        <div v-if="space.user_role_label" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-semibold">
                            <span>🛡️</span>
                            Peran Anda: {{ space.user_role_label }}
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                        <Link
                            :href="route('notes.create', { space: space.slug })"
                            class="text-xs font-semibold text-slate-600 hover:text-indigo-600 transition"
                        >
                            + Tulis Dokumen
                        </Link>
                        <Link
                            :href="route('spaces.show', space.slug)"
                            class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition"
                        >
                            Buka Space →
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
