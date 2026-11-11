<script setup>
import { ref, computed } from 'vue';
import { renderObsidianMarkdown } from '@/Utils/markdown';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Tulis dokumen Markdown Anda di sini...',
    },
    error: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const activeTab = ref('split'); // 'edit', 'preview', or 'split'
const textareaRef = ref(null);

const renderedHtml = computed(() => {
    return renderObsidianMarkdown(props.modelValue);
});

// Live Secret & PII scanner check
const securityWarnings = computed(() => {
    const warnings = [];
    const val = props.modelValue || '';

    if (/AKIA[0-9A-Z]{16}/.test(val)) {
        warnings.push('Terdeteksi AWS Access Key');
    }
    if (/gh[pousr]_[A-Za-z0-9_]{36,255}/.test(val)) {
        warnings.push('Terdeteksi GitHub Personal Access Token');
    }
    if (/-----BEGIN (RSA|EC|OPENSSH|DSA|PGP) PRIVATE KEY-----/.test(val)) {
        warnings.push('Terdeteksi Private Key rahasia');
    }
    if (/\b[1-9]\d{15}\b/.test(val)) {
        warnings.push('Terdeteksi format NIK (16 digit)');
    }

    return warnings;
});

function insertText(prefix, suffix = '', defaultText = '') {
    const textarea = textareaRef.value;
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selected = props.modelValue.substring(start, end) || defaultText;

    const replacement = prefix + selected + suffix;
    const newValue = props.modelValue.substring(0, start) + replacement + props.modelValue.substring(end);

    emit('update:modelValue', newValue);

    // Reposition cursor
    setTimeout(() => {
        textarea.focus();
        textarea.setSelectionRange(start + prefix.length, start + prefix.length + selected.length);
    }, 10);
}

function insertCallout(type = 'NOTE') {
    insertText(`> [!${type}]\n> `, '\n\n', 'Isi catatan penting di sini...');
}

function insertWikilink() {
    insertText('[[', ']]', 'Judul Catatan');
}
</script>

<template>
    <div class="border border-slate-300 rounded-xl overflow-hidden bg-white shadow-xs focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 transition">
        <!-- Toolbar -->
        <div class="bg-slate-50 border-b border-slate-200 px-3 py-2 flex flex-wrap items-center justify-between gap-2">
            <!-- Format buttons -->
            <div class="flex items-center gap-1 text-slate-600 text-xs font-medium">
                <button
                    type="button"
                    @click="insertText('**', '**', 'teks tebal')"
                    class="p-1.5 hover:bg-slate-200 rounded text-slate-700 font-bold transition"
                    title="Bold"
                >
                    B
                </button>
                <button
                    type="button"
                    @click="insertText('*', '*', 'teks miring')"
                    class="p-1.5 hover:bg-slate-200 rounded text-slate-700 italic transition"
                    title="Italic"
                >
                    I
                </button>
                <button
                    type="button"
                    @click="insertText('### ', '\n', 'Sub Judul')"
                    class="p-1.5 hover:bg-slate-200 rounded text-slate-700 font-bold transition"
                    title="Header 3"
                >
                    H3
                </button>

                <div class="h-4 w-px bg-slate-300 mx-1"></div>

                <button
                    type="button"
                    @click="insertText('- ', '\n', 'Item daftar')"
                    class="p-1.5 hover:bg-slate-200 rounded text-slate-700 transition"
                    title="Bullet List"
                >
                    • List
                </button>
                <button
                    type="button"
                    @click="insertText('1. ', '\n', 'Langkah')"
                    class="p-1.5 hover:bg-slate-200 rounded text-slate-700 transition"
                    title="Numbered List"
                >
                    1. List
                </button>
                <button
                    type="button"
                    @click="insertText('- [ ] ', '\n', 'Tugas checklist')"
                    class="p-1.5 hover:bg-slate-200 rounded text-slate-700 transition"
                    title="Checklist"
                >
                    ☑ Task
                </button>

                <div class="h-4 w-px bg-slate-300 mx-1"></div>

                <!-- Obsidian Specific syntax buttons -->
                <button
                    type="button"
                    @click="insertWikilink"
                    class="px-2 py-1 bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 rounded text-xs font-semibold flex items-center gap-1 transition"
                    title="Internal Wikilink Obsidian"
                >
                    [[ Wikilink ]]
                </button>

                <div class="relative inline-block text-left">
                    <button
                        type="button"
                        @click="insertCallout('NOTE')"
                        class="px-2 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded text-xs font-semibold transition"
                        title="Obsidian Callout"
                    >
                        > [!NOTE]
                    </button>
                </div>
                <button
                    type="button"
                    @click="insertCallout('TIP')"
                    class="px-2 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded text-xs font-semibold transition"
                    title="Obsidian Tip"
                >
                    > [!TIP]
                </button>
                <button
                    type="button"
                    @click="insertCallout('WARNING')"
                    class="px-2 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded text-xs font-semibold transition"
                    title="Obsidian Warning"
                >
                    > [!WARNING]
                </button>
            </div>

            <!-- View Switcher Tabs -->
            <div class="inline-flex rounded-lg bg-slate-200/80 p-0.5 text-xs font-medium text-slate-600">
                <button
                    type="button"
                    @click="activeTab = 'edit'"
                    :class="[activeTab === 'edit' ? 'bg-white text-slate-900 shadow-xs' : 'hover:text-slate-900']"
                    class="px-2.5 py-1 rounded-md transition"
                >
                    Edit
                </button>
                <button
                    type="button"
                    @click="activeTab = 'split'"
                    :class="[activeTab === 'split' ? 'bg-white text-slate-900 shadow-xs' : 'hover:text-slate-900']"
                    class="px-2.5 py-1 rounded-md transition hidden md:block"
                >
                    Split
                </button>
                <button
                    type="button"
                    @click="activeTab = 'preview'"
                    :class="[activeTab === 'preview' ? 'bg-white text-slate-900 shadow-xs' : 'hover:text-slate-900']"
                    class="px-2.5 py-1 rounded-md transition"
                >
                    Pratinjau
                </button>
            </div>
        </div>

        <!-- Security Warning Banner if secrets detected -->
        <div v-if="securityWarnings.length > 0" class="bg-amber-50 border-b border-amber-200 px-4 py-2 text-xs text-amber-800 flex items-center gap-2">
            <span class="font-bold">⚠️ Peringatan Keamanan (PRD FR-11):</span>
            <span>{{ securityWarnings.join(' • ') }}</span>
        </div>

        <!-- Content Area -->
        <div class="relative">
            <!-- Edit Only View -->
            <div v-show="activeTab === 'edit'" class="p-4">
                <textarea
                    ref="textareaRef"
                    :value="modelValue"
                    @input="$emit('update:modelValue', $event.target.value)"
                    rows="18"
                    class="w-full font-mono text-sm border-0 focus:ring-0 p-0 resize-y text-slate-800 placeholder-slate-400 focus:outline-none"
                    :placeholder="placeholder"
                ></textarea>
            </div>

            <!-- Split View -->
            <div v-show="activeTab === 'split'" class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200 min-h-[450px]">
                <div class="p-4 bg-white">
                    <textarea
                        ref="textareaRef"
                        :value="modelValue"
                        @input="$emit('update:modelValue', $event.target.value)"
                        rows="18"
                        class="w-full h-full font-mono text-sm border-0 focus:ring-0 p-0 resize-none text-slate-800 placeholder-slate-400 focus:outline-none"
                        :placeholder="placeholder"
                    ></textarea>
                </div>
                <div class="p-6 bg-slate-50/50 overflow-y-auto max-h-[600px] prose prose-slate max-w-none text-sm">
                    <div v-if="!modelValue" class="text-slate-400 italic">
                        Pratinjau Markdown Obsidian akan muncul di sini...
                    </div>
                    <div v-else v-html="renderedHtml"></div>
                </div>
            </div>

            <!-- Preview Only View -->
            <div v-show="activeTab === 'preview'" class="p-6 bg-slate-50/30 min-h-[400px] overflow-y-auto prose prose-slate max-w-none text-sm">
                <div v-if="!modelValue" class="text-slate-400 italic">
                    Pratinjau Markdown Obsidian akan muncul di sini...
                </div>
                <div v-else v-html="renderedHtml"></div>
            </div>
        </div>

        <!-- Word count & Status Footer -->
        <div class="bg-slate-50 border-t border-slate-200 px-4 py-1.5 flex items-center justify-between text-xs text-slate-500 font-mono">
            <div>
                {{ modelValue ? modelValue.split(/\s+/).filter(Boolean).length : 0 }} kata • {{ modelValue ? modelValue.length : 0 }} karakter
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Kompatibel Format Obsidian</span>
            </div>
        </div>
    </div>
    <div v-if="error" class="text-xs text-rose-600 mt-1.5 font-medium">
        {{ error }}
    </div>
</template>

<style>
/* Basic styling for generated Markdown */
.prose h1 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-top: 1rem;
    margin-bottom: 0.5rem;
    color: #0f172a;
}
.prose h2 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-top: 1rem;
    margin-bottom: 0.5rem;
    color: #1e293b;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 0.25rem;
}
.prose h3 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-top: 0.75rem;
    margin-bottom: 0.25rem;
    color: #334155;
}
.prose p {
    margin-bottom: 0.75rem;
    line-height: 1.6;
}
.prose ul {
    list-style-type: disc;
    padding-left: 1.25rem;
    margin-bottom: 0.75rem;
}
.prose ol {
    list-style-type: decimal;
    padding-left: 1.25rem;
    margin-bottom: 0.75rem;
}
.prose code {
    background-color: #f1f5f9;
    padding: 0.15rem 0.3rem;
    border-radius: 0.25rem;
    font-family: monospace;
    font-size: 0.85em;
    color: #0f172a;
}
.prose pre {
    background-color: #0f172a;
    color: #f8fafc;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin-bottom: 0.75rem;
}
.prose pre code {
    background-color: transparent;
    color: inherit;
    padding: 0;
}
</style>
