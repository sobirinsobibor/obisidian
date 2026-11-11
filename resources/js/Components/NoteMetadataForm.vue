<script setup>
import { ref } from 'vue';

const props = defineProps({
    modelValue: {
        type: Object,
        required: true,
    },
    spaces: {
        type: Array,
        default: () => [],
    },
    types: {
        type: Array,
        default: () => [],
    },
    visibilities: {
        type: Array,
        default: () => [],
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['update:modelValue']);

const newTagInput = ref('');
const showYamlPreview = ref(false);

function addTag() {
    const val = newTagInput.value.trim().toLowerCase().replace(/\s+/g, '-');
    if (!val) return;

    const currentTags = [...(props.modelValue.tags || [])];
    if (!currentTags.includes(val)) {
        currentTags.push(val);
        emit('update:modelValue', {
            ...props.modelValue,
            tags: currentTags,
        });
    }
    newTagInput.value = '';
}

function removeTag(tagToRemove) {
    const currentTags = (props.modelValue.tags || []).filter(t => t !== tagToRemove);
    emit('update:modelValue', {
        ...props.modelValue,
        tags: currentTags,
    });
}
</script>

<template>
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-semibold text-slate-800 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                Metadata Dokumen (Obsidian Frontmatter)
            </h3>
            <button
                type="button"
                @click="showYamlPreview = !showYamlPreview"
                class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition"
            >
                {{ showYamlPreview ? 'Tutup Preview YAML' : 'Lihat Preview YAML' }}
            </button>
        </div>

        <!-- Optional YAML Frontmatter Preview -->
        <div v-if="showYamlPreview" class="p-3 bg-slate-900 rounded-lg text-slate-200 font-mono text-xs overflow-x-auto">
            <pre>---
id: {{ modelValue.kb_id || 'kb-auto' }}
title: "{{ modelValue.title || '' }}"
space: {{ spaces.find(s => s.id === modelValue.space_id)?.slug || 'space' }}
type: {{ modelValue.type }}
product: {{ modelValue.product || 'general' }}
visibility: {{ modelValue.visibility }}
status: {{ modelValue.status || 'draft' }}
tags: [{{ (modelValue.tags || []).join(', ') }}]
---</pre>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Space Picker -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Space / Divisi <span class="text-rose-500">*</span></label>
                <select
                    :value="modelValue.space_id"
                    @change="$emit('update:modelValue', { ...modelValue, space_id: $event.target.value })"
                    class="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white"
                >
                    <option value="" disabled>Pilih Space...</option>
                    <option v-for="space in spaces" :key="space.id" :value="space.id">
                        {{ space.name }} ({{ space.slug }})
                    </option>
                </select>
                <div v-if="errors.space_id" class="text-xs text-rose-500 mt-1">{{ errors.space_id }}</div>
            </div>

            <!-- Note Type -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe Catatan <span class="text-rose-500">*</span></label>
                <select
                    :value="modelValue.type"
                    @change="$emit('update:modelValue', { ...modelValue, type: $event.target.value })"
                    class="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white"
                >
                    <option v-for="t in types" :key="t.value" :value="t.value">
                        {{ t.label }}
                    </option>
                </select>
                <div v-if="errors.type" class="text-xs text-rose-500 mt-1">{{ errors.type }}</div>
            </div>

            <!-- Product Identifier -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Produk Terkait</label>
                <input
                    type="text"
                    :value="modelValue.product"
                    @input="$emit('update:modelValue', { ...modelValue, product: $event.target.value })"
                    placeholder="mis. portal-core, app-mobile"
                    class="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>

            <!-- Visibility Level -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Visibilitas <span class="text-rose-500">*</span></label>
                <select
                    :value="modelValue.visibility"
                    @change="$emit('update:modelValue', { ...modelValue, visibility: $event.target.value })"
                    class="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white"
                >
                    <option v-for="v in visibilities" :key="v.value" :value="v.value">
                        {{ v.label }}
                    </option>
                </select>
                <div v-if="errors.visibility" class="text-xs text-rose-500 mt-1">{{ errors.visibility }}</div>
            </div>
        </div>

        <!-- Tags Input (Interactive Chips) -->
        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Tags (Tekan Enter untuk menambah)</label>
            <div class="flex flex-wrap items-center gap-1.5 p-2 border border-slate-300 rounded-lg bg-slate-50/50 min-h-[38px] focus-within:bg-white focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 transition">
                <span
                    v-for="tag in modelValue.tags || []"
                    :key="tag"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200 text-xs font-medium"
                >
                    #{{ tag }}
                    <button
                        type="button"
                        @click="removeTag(tag)"
                        class="text-indigo-400 hover:text-indigo-700 font-bold"
                    >
                        ×
                    </button>
                </span>
                <input
                    type="text"
                    v-model="newTagInput"
                    @keydown.enter.prevent="addTag"
                    @keydown.comma.prevent="addTag"
                    placeholder="Tambah tag..."
                    class="border-0 bg-transparent text-xs p-0 focus:ring-0 flex-1 min-w-[120px] text-slate-800"
                />
            </div>
        </div>
    </div>
</template>
