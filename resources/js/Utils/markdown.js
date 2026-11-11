import { marked } from 'marked';

// Configure marked
marked.setOptions({
    gfm: true,
    breaks: true,
});

/**
 * Pre-process Obsidian Markdown extensions before standard markdown parsing:
 * - Obsidian Callouts: > [!NOTE], > [!TIP], > [!WARNING], > [!IMPORTANT], > [!CAUTION]
 * - Wikilinks: [[target-note]] or [[target-note|Display Text]]
 */
export function renderObsidianMarkdown(rawText) {
    if (!rawText) return '';

    let text = rawText;

    // 1. Process Obsidian Callouts
    // Match lines starting with > [!TYPE] (Title optional)
    const calloutRegex = /^>\s*\[!(NOTE|TIP|IMPORTANT|WARNING|CAUTION|INFO|DANGER)\]([^\n]*)\n((?:>.*\n?)*)/gim;

    text = text.replace(calloutRegex, (match, type, title, body) => {
        const calloutType = type.toUpperCase();
        const cleanTitle = title.trim() || calloutType;
        
        // Strip leading `>` from each line of body
        const cleanBody = body
            .split('\n')
            .map(line => line.replace(/^>\s?/, ''))
            .join('\n');

        let themeClasses = 'border-blue-500 bg-blue-50 text-blue-950';
        let icon = 'ℹ️';

        if (calloutType === 'TIP') {
            themeClasses = 'border-emerald-500 bg-emerald-50 text-emerald-950';
            icon = '💡';
        } else if (calloutType === 'WARNING') {
            themeClasses = 'border-amber-500 bg-amber-50 text-amber-950';
            icon = '⚠️';
        } else if (calloutType === 'CAUTION' || calloutType === 'DANGER') {
            themeClasses = 'border-rose-500 bg-rose-50 text-rose-950';
            icon = '🛑';
        } else if (calloutType === 'IMPORTANT') {
            themeClasses = 'border-purple-500 bg-purple-50 text-purple-950';
            icon = '📌';
        }

        return `\n<div class="my-4 rounded-r-lg border-l-4 ${themeClasses} p-4 text-sm shadow-xs">\n<div class="font-bold flex items-center gap-1.5 mb-1 text-xs tracking-wider uppercase">${icon} ${cleanTitle}</div>\n<div>${cleanBody}</div>\n</div>\n`;
    });

    // 2. Process Wikilinks: [[SlugOrTitle]] or [[SlugOrTitle|Display Text]]
    const wikilinkRegex = /\[\[([a-zA-Z0-9_\-\s]+)(?:\|([^\]]+))?\]\]/g;
    text = text.replace(wikilinkRegex, (match, target, alias) => {
        const label = alias || target;
        return `<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200 font-mono text-xs cursor-pointer hover:bg-purple-100 transition">📄 ${label}</span>`;
    });

    // 3. Render HTML via marked
    return marked.parse(text);
}
