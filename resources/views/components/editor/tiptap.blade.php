@props([
    'name' => 'content',
    'value' => '',
    'placeholder' => 'Digite seu conteúdo...',
    'height' => '400px',
])

<div
    x-data="tiptapEditor({
        name: '{{ $name }}',
        content: @js($value),
        placeholder: '{{ $placeholder }}'
    })"
    x-init="init()"
    class="border border-(--border-color) rounded-lg overflow-hidden"
>
    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center gap-1 p-2 bg-(--bg-elevated) border-b border-(--border-color)">
        {{-- Bold --}}
        <button
            type="button"
            @click="editor.chain().focus().toggleBold().run()"
            :class="{ 'bg-(--bg-hover)': editor.isActive('bold') }"
            class="p-2 rounded hover:bg-(--bg-hover)"
            title="Negrito (Ctrl+B)"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path>
            </svg>
        </button>

        {{-- Italic --}}
        <button
            type="button"
            @click="editor.chain().focus().toggleItalic().run()"
            :class="{ 'bg-(--bg-hover)': editor.isActive('italic') }"
            class="p-2 rounded hover:bg-(--bg-hover)"
            title="Itálico (Ctrl+I)"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 4h4m-2 0v16m-4 0h8"></path>
            </svg>
        </button>

        {{-- Underline --}}
        <button
            type="button"
            @click="editor.chain().focus().toggleUnderline().run()"
            :class="{ 'bg-(--bg-hover)': editor.isActive('underline') }"
            class="p-2 rounded hover:bg-(--bg-hover)"
            title="Sublinhado (Ctrl+U)"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v7a5 5 0 0 0 10 0V4M5 21h14"></path>
            </svg>
        </button>

        {{-- Strikethrough --}}
        <button
            type="button"
            @click="editor.chain().focus().toggleStrike().run()"
            :class="{ 'bg-(--bg-hover)': editor.isActive('strike') }"
            class="p-2 rounded hover:bg-(--bg-hover)"
            title="Tachado"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 12H7m10-6H7m6 12H7"></path>
            </svg>
        </button>

        <div class="w-px h-6 bg-(--border-color) mx-1"></div>

        {{-- Heading 1 --}}
        <button
            type="button"
            @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
            :class="{ 'bg-(--bg-hover)': editor.isActive('heading', { level: 1 }) }"
            class="p-2 rounded hover:bg-(--bg-hover)"
            title="Título 1"
        >
            <span class="text-sm font-bold">H1</span>
        </button>

        {{-- Heading 2 --}}
        <button
            type="button"
            @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
            :class="{ 'bg-(--bg-hover)': editor.isActive('heading', { level: 2 }) }"
            class="p-2 rounded hover:bg-(--bg-hover)"
            title="Título 2"
        >
            <span class="text-sm font-bold">H2</span>
        </button>

        {{-- Heading 3 --}}
        <button
            type="button"
            @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
            :class="{ 'bg-(--bg-hover)': editor.isActive('heading', { level: 3 }) }"
            class="p-2 rounded hover:bg-(--bg-hover)"
            title="Título 3"
        >
            <span class="text-sm font-bold">H3</span>
        </button>

        <div class="w-px h-6 bg-(--border-color) mx-1"></div>

        {{-- Bullet List --}}
        <button
            type="button"
            @click="editor.chain().focus().toggleBulletList().run()"
            :class="{ 'bg-(--bg-hover)': editor.isActive('bulletList') }"
            class="p-2 rounded hover:bg-(--bg-hover)"
            title="Lista com marcadores"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"></path>
            </svg>
        </button>

        {{-- Ordered List --}}
        <button
            type="button"
            @click="editor.chain().focus().toggleOrderedList().run()"
            :class="{ 'bg-(--bg-hover)': editor.isActive('orderedList') }"
            class="p-2 rounded hover:bg-(--bg-hover)"
            title="Lista numerada"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 6h13M7 12h13M7 18h13M3 6h.01M3 12h.01M3 18h.01"></path>
            </svg>
        </button>

        <div class="w-px h-6 bg-(--border-color) mx-1"></div>

        {{-- Blockquote --}}
        <button
            type="button"
            @click="editor.chain().focus().toggleBlockquote().run()"
            :class="{ 'bg-(--bg-hover)': editor.isActive('blockquote') }"
            class="p-2 rounded hover:bg-(--bg-hover)"
            title="Citação"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
        </button>

        {{-- Code Block --}}
        <button
            type="button"
            @click="editor.chain().focus().toggleCodeBlock().run()"
            :class="{ 'bg-(--bg-hover)': editor.isActive('codeBlock') }"
            class="p-2 rounded hover:bg-(--bg-hover)"
            title="Bloco de código"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
            </svg>
        </button>

        <div class="w-px h-6 bg-(--border-color) mx-1"></div>

        {{-- Link --}}
        <button
            type="button"
            @click="setLink()"
            :class="{ 'bg-(--bg-hover)': editor.isActive('link') }"
            class="p-2 rounded hover:bg-(--bg-hover)"
            title="Link"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
            </svg>
        </button>

        {{-- Image --}}
        <button
            type="button"
            @click="addImage()"
            class="p-2 rounded hover:bg-(--bg-hover)"
            title="Imagem"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
        </button>

        <div class="w-px h-6 bg-(--border-color) mx-1"></div>

        {{-- Align Left --}}
        <button
            type="button"
            @click="editor.chain().focus().setTextAlign('left').run()"
            :class="{ 'bg-(--bg-hover)': editor.isActive({ textAlign: 'left' }) }"
            class="p-2 rounded hover:bg-(--bg-hover)"
            title="Alinhar à esquerda"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M3 12h12M3 18h16"></path>
            </svg>
        </button>

        {{-- Align Center --}}
        <button
            type="button"
            @click="editor.chain().focus().setTextAlign('center').run()"
            :class="{ 'bg-(--bg-hover)': editor.isActive({ textAlign: 'center' }) }"
            class="p-2 rounded hover:bg-(--bg-hover)"
            title="Centralizar"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M8 12h8M4 18h12"></path>
            </svg>
        </button>

        {{-- Align Right --}}
        <button
            type="button"
            @click="editor.chain().focus().setTextAlign('right').run()"
            :class="{ 'bg-(--bg-hover)': editor.isActive({ textAlign: 'right' }) }"
            class="p-2 rounded hover:bg-(--bg-hover)"
            title="Alinhar à direita"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M9 12h12M5 18h16"></path>
            </svg>
        </button>

        <div class="w-px h-6 bg-(--border-color) mx-1"></div>

        {{-- Highlight --}}
        <button
            type="button"
            @click="editor.chain().focus().toggleHighlight().run()"
            :class="{ 'bg-(--bg-hover)': editor.isActive('highlight') }"
            class="p-2 rounded hover:bg-(--bg-hover)"
            title="Destaque"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
            </svg>
        </button>

        <div class="w-px h-6 bg-(--border-color) mx-1"></div>

        {{-- Horizontal Rule --}}
        <button
            type="button"
            @click="editor.chain().focus().setHorizontalRule().run()"
            class="p-2 rounded hover:bg-(--bg-hover)"
            title="Linha horizontal"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
            </svg>
        </button>

        {{-- Undo --}}
        <button
            type="button"
            @click="editor.chain().focus().undo().run()"
            :disabled="!editor.can().undo()"
            class="p-2 rounded hover:bg-(--bg-hover) disabled:opacity-50"
            title="Desfazer (Ctrl+Z)"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
            </svg>
        </button>

        {{-- Redo --}}
        <button
            type="button"
            @click="editor.chain().focus().redo().run()"
            :disabled="!editor.can().redo()"
            class="p-2 rounded hover:bg-(--bg-hover) disabled:opacity-50"
            title="Refazer (Ctrl+Shift+Z)"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a8 8 0 00-8 8v2m18-10l-6 6m6-6l-6-6"></path>
            </svg>
        </button>
    </div>

    {{-- Editor --}}
    <div
        x-ref="editor"
        class="prose prose-sm sm:prose max-w-none p-4 focus:outline-none dark:prose-invert"
        style="min-height: {{ $height }};"
    ></div>

    {{-- Hidden Input --}}
    <input type="hidden" name="{{ $name }}" x-model="content">
</div>

@script
<script type="module">
    import { Editor } from '@tiptap/core';
    import StarterKit from '@tiptap/starter-kit';
    import Image from '@tiptap/extension-image';
    import Link from '@tiptap/extension-link';
    import Placeholder from '@tiptap/extension-placeholder';
    import TextAlign from '@tiptap/extension-text-align';
    import Underline from '@tiptap/extension-underline';
    import Highlight from '@tiptap/extension-highlight';
    import Table from '@tiptap/extension-table';
    import TableRow from '@tiptap/extension-table-row';
    import TableCell from '@tiptap/extension-table-cell';
    import TableHeader from '@tiptap/extension-table-header';

    Alpine.data('tiptapEditor', (config) => ({
        editor: null,
        content: config.content || '',

        init() {
            this.editor = new Editor({
                element: this.$refs.editor,
                extensions: [
                    StarterKit,
                    Image.configure({
                        inline: true,
                        allowBase64: true,
                    }),
                    Link.configure({
                        openOnClick: false,
                    }),
                    Placeholder.configure({
                        placeholder: config.placeholder,
                    }),
                    TextAlign.configure({
                        types: ['heading', 'paragraph'],
                    }),
                    Underline,
                    Highlight,
                    Table.configure({
                        resizable: true,
                    }),
                    TableRow,
                    TableCell,
                    TableHeader,
                ],
                content: this.content,
                onUpdate: ({ editor }) => {
                    this.content = editor.getHTML();
                    this.$dispatch('content-change', this.content);
                },
            });
        },

        setLink() {
            const url = window.prompt('URL do link:');
            if (url) {
                this.editor.chain().focus().setLink({ href: url }).run();
            }
        },

        addImage() {
            const url = window.prompt('URL da imagem:');
            if (url) {
                this.editor.chain().focus().setImage({ src: url }).run();
            }
        },
    }));
</script>
@endscript
