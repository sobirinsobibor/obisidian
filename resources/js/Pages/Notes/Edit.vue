<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MarkdownEditor from '@/Components/MarkdownEditor.vue';
import NoteMetadataForm from '@/Components/NoteMetadataForm.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    note: Object,
    spaces: Array,
    types: Array,
    visibilities: Array,
});

const form = useForm({
    title: props.note.title,
    space_id: props.note.space_id,
    type: props.note.type,
    product: props.note.product || 'general',
    visibility: props.note.visibility,
    tags: props.note.tags || [],
    content: props.note.content || '',
    action: 'save_draft',
});

function submit(actionType) {
    form.action = actionType;
    form.put(route('notes.update', props.note.id));
}
</script>

<template>
    <Head :title="`Edit - ${note.title}`" />

    <AuthenticatedLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                        <Link :href="route('notes.index')" class="hover:text-indigo-600">Catatan</Link>
                        <span>/</span>
                        <Link :href="route('notes.show', note.id)" class="hover:text-indigo-600">{{ note.kb_id }}</Link>
                        <span>/</span>
                        <span>Edit</span>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        Edit Dokumen: {{ note.kb_id }}
                    </h1>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('notes.show', note.id)"
                        class="px-4 py-2 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition shadow-xs"
                    >
                        Batal
                    </Link>
                    <button
                        type="button"
                        :disabled="form.processing"
                        @click="submit('save_draft')"
                        class="px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition shadow-xs disabled:opacity-50"
                    >
                        Simpan Draft
                    </button>
                    <button
                        type="button"
                        :disabled="form.processing"
                        @click="submit('submit_review')"
                        class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-xs disabled:opacity-50 flex items-center gap-1.5"
                    >
                        <span>🚀</span>
                        Ajukan Review
                    </button>
                </div>
            </div>

            <!-- Title Input -->
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-xs">
                <label class="block text-xs font-semibold text-slate-500 mb-1">Judul Dokumen <span class="text-rose-500">*</span></label>
                <input
                    type="text"
                    v-model="form.title"
                    class="w-full text-lg font-bold border-0 p-0 text-slate-900 focus:ring-0 placeholder-slate-300"
                />
                <div v-if="form.errors.title" class="text-xs text-rose-500 mt-1 font-medium">{{ form.errors.title }}</div>
            </div>

            <!-- Structured Metadata Form -->
            <NoteMetadataForm
                v-model="form"
                :spaces="spaces"
                :types="types"
                :visibilities="visibilities"
                :errors="form.errors"
            />

            <!-- Markdown Editor -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Konten Catatan (Sintaks Obsidian) <span class="text-rose-500">*</span>
                </label>
                <MarkdownEditor
                    v-model="form.content"
                    :error="form.errors.content"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
