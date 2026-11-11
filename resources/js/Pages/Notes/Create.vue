<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MarkdownEditor from '@/Components/MarkdownEditor.vue';
import NoteMetadataForm from '@/Components/NoteMetadataForm.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    spaces: Array,
    types: Array,
    visibilities: Array,
    templates: Array,
    defaultSpace: String,
});

const form = useForm({
    title: '',
    space_id: props.defaultSpace ? (props.spaces.find(s => s.slug === props.defaultSpace)?.id || '') : (props.spaces[0]?.id || ''),
    type: 'runbook',
    product: 'general',
    visibility: 'internal',
    tags: [],
    content: props.templates.find(t => t.type === 'runbook')?.content || '',
    action: 'save_draft', // 'save_draft' | 'submit_review'
});

const selectedTemplate = ref('runbook');

function applyTemplate(type) {
    const t = props.templates.find(item => item.type === type);
    if (t) {
        if (!form.content || confirm('Ganti isi catatan saat ini dengan template baru?')) {
            form.content = t.content;
            form.type = t.type;
        }
    }
}

function submit(actionType) {
    form.action = actionType;
    form.post(route('notes.store'));
}
</script>

<template>
    <Head title="Tulis Catatan Baru" />

    <AuthenticatedLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Breadcrumb & Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                        <Link :href="route('notes.index')" class="hover:text-indigo-600">Catatan</Link>
                        <span>/</span>
                        <span>Buat Baru</span>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        Tulis Dokumen Pengetahuan
                    </h1>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2">
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
                    placeholder="Contoh: Cara Reset Password Pengguna Melalui Admin Portal"
                    class="w-full text-lg font-bold border-0 p-0 text-slate-900 focus:ring-0 placeholder-slate-300"
                />
                <div v-if="form.errors.title" class="text-xs text-rose-500 mt-1 font-medium">{{ form.errors.title }}</div>
            </div>

            <!-- Template Quick Picker (PRD FR-05) -->
            <div class="bg-slate-100/70 border border-slate-200 rounded-xl p-3 flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-slate-600 mr-1 flex items-center gap-1">
                    <span>📋</span> Pilih Template:
                </span>
                <button
                    v-for="tmpl in templates"
                    :key="tmpl.type"
                    type="button"
                    @click="applyTemplate(tmpl.type)"
                    :class="[
                        form.type === tmpl.type ? 'bg-indigo-600 text-white font-medium shadow-xs' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'
                    ]"
                    class="px-2.5 py-1 text-xs rounded-lg transition"
                >
                    {{ tmpl.label }}
                </button>
            </div>

            <!-- Structured Metadata Form (PRD FR-04) -->
            <NoteMetadataForm
                v-model="form"
                :spaces="spaces"
                :types="types"
                :visibilities="visibilities"
                :errors="form.errors"
            />

            <!-- Markdown Editor (PRD FR-03) -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Konten Catatan (Sintaks Obsidian) <span class="text-rose-500">*</span>
                </label>
                <MarkdownEditor
                    v-model="form.content"
                    :error="form.errors.content"
                    placeholder="Mulai tulis instruksi atau panduan di sini. Gunakan [[wikilink]] dan > [!NOTE] callout Obsidian..."
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
