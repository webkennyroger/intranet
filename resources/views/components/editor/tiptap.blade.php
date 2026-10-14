{{--
Editor de texto rico (WYSIWYG) baseado em Tiptap, usado nos formulários
que precisam de conteúdo formatado (ex.: artigos). A toolbar chama a API
do Tiptap através dos métodos cmd()/isActive()/can() (ver app.js) — nunca
direto por "editor.", porque guardar a instância do Tiptap como uma
propriedade comum do Alpine (x-data) a torna reativa, e isso corrompia o
estado interno do ProseMirror a cada comando ("mismatched transaction").
O HTML gerado fica sincronizado num input hidden ("name"), enviado no
submit do formulário. "wire:ignore" é essencial: o Tiptap controla este
bloco de DOM por fora do Livewire, e sem isso cada re-render do
componente (ex.: o $wire.set disparado a cada edição) faz o Livewire
"remorfar" a div do editor de volta pro HTML vazio que o servidor
renderiza, apagando tudo que foi digitado. O botão de imagem insere a
caixa "Drop files here" (ImagePlaceholder, ver app.js) em vez de abrir
o seletor de arquivo direto — ela tem a própria barrinha (enviar
arquivo ou colar uma URL). O upload de verdade sobe pelo único input
de arquivo real do componente ($refs.imageInput, sempre oculto) via
Livewire (wire:model="{uploadModel}", padrão "bodyImage" — o
componente host precisa de uma propriedade com esse nome e um método
updated{Nome}() que salve o arquivo e dispare "tiptap-image-inserted"
com a URL) em vez de embutir base64, que inflava o payload do Livewire
acima do limite em imagens de tamanho normal. handleImageInserted()
decide o que fazer com essa URL: preencher a aba "Upload" do modal de
link, resolver um ImagePlaceholder pendente, ou (fallback) inserir
direto no cursor — tudo sem precisar de mais nenhuma propriedade/
método novo no host.
--}}
@props([
    'name' => 'content',
    'value' => '',
    'placeholder' => 'Digite seu conteúdo...',
    'height' => '400px',
    'uploadModel' => 'bodyImage',
])

<div wire:ignore x-data="tiptapEditor({
        name: '{{ $name }}',
        content: @js($value),
        placeholder: '{{ $placeholder }}'
    })" x-init="init()" x-on:tiptap-image-inserted.window="handleImageInserted($event.detail.url)"
    x-on:open-link-modal="openLinkModal()"
    x-on:image-placeholder-pick="pendingPlaceholderPos = $event.detail.pos; $refs.imageInput.click()"
    x-on:image-placeholder-file="receiveDroppedFile($event.detail.pos, $event.detail.file)"
    x-on:image-placeholder-url="resolvePlaceholderToImage($event.detail.pos, $event.detail.url)"
    x-on:open-table-properties="openTablePropertiesModal()"
    :class="fullscreen ? 'fixed inset-0 z-50 bg-(--bg-surface) rounded-none' : 'relative border border-(--border-color) rounded-lg'">
    {{-- Toolbar: "rounded-t-lg" (em vez de "overflow-hidden" no wrapper
    inteiro) pra combinar com os cantos arredondados do wrapper sem
    cortar os menus flutuantes (Tabela/Formato/Alinhar/Cor/Link/etc.)
    quando o botão deles fica perto da borda direita do editor. --}}
    <div
        class="editor-toolbar flex flex-wrap items-center gap-0.5 p-1.5 rounded-t-lg bg-(--bg-elevated) border-b border-(--border-color)">

        {{-- Bold --}}
        <button type="button" @mousedown.prevent @click="cmd(e => e.chain().toggleBold().run())"
            :class="{ 'bg-(--bg-hover)': isActive('bold') }" class="p-2 rounded cursor-pointer hover:bg-(--bg-hover)"
            data-tip="Negrito (Ctrl+B)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path>
            </svg>
        </button>

        {{-- Italic --}}
        <button type="button" @mousedown.prevent @click="cmd(e => e.chain().toggleItalic().run())"
            :class="{ 'bg-(--bg-hover)': isActive('italic') }" class="p-2 rounded cursor-pointer hover:bg-(--bg-hover)"
            data-tip="Itálico (Ctrl+I)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 4h4m-2 0v16m-4 0h8"></path>
            </svg>
        </button>

        {{-- Underline --}}
        <button type="button" @mousedown.prevent @click="cmd(e => e.chain().toggleUnderline().run())"
            :class="{ 'bg-(--bg-hover)': isActive('underline') }"
            class="p-2 rounded cursor-pointer hover:bg-(--bg-hover)" data-tip="Sublinhado (Ctrl+U)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 4v7a5 5 0 0 0 10 0V4M5 21h14"></path>
            </svg>
        </button>

        {{-- Strikethrough --}}
        <button type="button" @mousedown.prevent @click="cmd(e => e.chain().toggleStrike().run())"
            :class="{ 'bg-(--bg-hover)': isActive('strike') }" class="p-2 rounded cursor-pointer hover:bg-(--bg-hover)"
            data-tip="Tachado">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 12H7m10-6H7m6 12H7"></path>
            </svg>
        </button>

        <div class="w-px h-6 bg-(--border-color) mx-1"></div>

        {{-- Cor do texto --}}
        <div class="relative" @click.outside="colorMenuOpen === 'text' && closeColorMenu()">
            <button type="button" @mousedown.prevent @click="toggleColorMenu('text')"
                class="flex items-center gap-0.5 p-2 rounded cursor-pointer hover:bg-(--bg-hover)"
                data-tip="Cor do texto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 19h14M7 15l5-11 5 11M8.5 11.5h7"></path>
                </svg>
                <svg class="w-3 h-3 text-(--text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div x-show="colorMenuOpen === 'text'" x-cloak
                class="absolute left-0 top-full mt-1 p-2 w-48 rounded-lg shadow-xl border border-(--border-color) bg-(--bg-surface) z-40">
                <div class="grid grid-cols-6 gap-1">
                    <template x-for="hex in colorPresets" :key="hex">
                        <button type="button" @mousedown.prevent @click="applyPresetColor('text', hex)"
                            class="w-6 h-6 rounded border border-(--border-color)" :style="`background-color: ${hex};`"
                            :data-tip="hex"></button>
                    </template>
                </div>
                <div class="flex items-center justify-between mt-2 pt-2 border-t border-(--border-color)">
                    <button type="button" @mousedown.prevent @click="removeColor('text')"
                        class="p-1.5 rounded hover:bg-(--bg-hover) text-(--text-secondary)" data-tip="Remover cor">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 20L20 4M6 4h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2z"></path>
                        </svg>
                    </button>
                    <button type="button" @mousedown.prevent @click="openColorModal('text')"
                        class="p-1.5 rounded hover:bg-(--bg-hover) text-(--text-secondary)"
                        data-tip="Cor personalizada">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Cor de destaque --}}
        <div class="relative" @click.outside="colorMenuOpen === 'highlight' && closeColorMenu()">
            <button type="button" @mousedown.prevent @click="toggleColorMenu('highlight')"
                class="flex items-center gap-0.5 p-2 rounded cursor-pointer hover:bg-(--bg-hover)"
                data-tip="Cor de destaque">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01">
                    </path>
                </svg>
                <svg class="w-3 h-3 text-(--text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div x-show="colorMenuOpen === 'highlight'" x-cloak
                class="absolute left-0 top-full mt-1 p-2 w-48 rounded-lg shadow-xl border border-(--border-color) bg-(--bg-surface) z-40">
                <div class="grid grid-cols-6 gap-1">
                    <template x-for="hex in colorPresets" :key="hex">
                        <button type="button" @mousedown.prevent @click="applyPresetColor('highlight', hex)"
                            class="w-6 h-6 rounded border border-(--border-color)" :style="`background-color: ${hex};`"
                            :data-tip="hex"></button>
                    </template>
                </div>
                <div class="flex items-center justify-between mt-2 pt-2 border-t border-(--border-color)">
                    <button type="button" @mousedown.prevent @click="removeColor('highlight')"
                        class="p-1.5 rounded hover:bg-(--bg-hover) text-(--text-secondary)" data-tip="Remover cor">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 20L20 4M6 4h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2z"></path>
                        </svg>
                    </button>
                    <button type="button" @mousedown.prevent @click="openColorModal('highlight')"
                        class="p-1.5 rounded hover:bg-(--bg-hover) text-(--text-secondary)"
                        data-tip="Cor personalizada">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="w-px h-6 bg-(--border-color) mx-1"></div>

        {{-- Aa: lowercase / UPPERCASE / Title Case --}}
        <div class="relative" @click.outside="closeCaseMenu()">
            <button type="button" @mousedown.prevent @click="toggleCaseMenu()"
                class="flex items-center gap-0.5 p-2 rounded cursor-pointer hover:bg-(--bg-hover)"
                data-tip="Maiúsculas/minúsculas">
                <span class="text-sm font-semibold leading-none">Aa</span>
                <svg class="w-3 h-3 text-(--text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div x-show="caseMenuOpen" x-cloak
                class="absolute left-0 top-full mt-1 w-36 rounded-lg shadow-xl border border-(--border-color) bg-(--bg-surface) py-1 z-40">
                <button type="button" @mousedown.prevent @click="transformCase('lowercase')"
                    class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm hover:bg-(--bg-hover)">
                    minúsculas
                    <svg x-show="detectTextCase() === 'lowercase'" class="w-3.5 h-3.5"
                        style="color: var(--admin-primary, var(--brand-primary, #0C4A34));" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </button>
                <button type="button" @mousedown.prevent @click="transformCase('uppercase')"
                    class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm hover:bg-(--bg-hover)">
                    MAIÚSCULAS
                    <svg x-show="detectTextCase() === 'uppercase'" class="w-3.5 h-3.5"
                        style="color: var(--admin-primary, var(--brand-primary, #0C4A34));" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </button>
                <button type="button" @mousedown.prevent @click="transformCase('titlecase')"
                    class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm hover:bg-(--bg-hover)">
                    Primeira Letra Maiúscula
                    <svg x-show="detectTextCase() === 'titlecase'" class="w-3.5 h-3.5"
                        style="color: var(--admin-primary, var(--brand-primary, #0C4A34));" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Tamanho da fonte --}}
        <div class="flex items-center gap-0.5">
            <button type="button" @mousedown.prevent @click="setFontSize(fontSizeValue() - 1)"
                class="w-7 h-7 flex items-center justify-center rounded cursor-pointer hover:bg-(--bg-hover) font-bold text-sm"
                data-tip="Diminuir fonte">−</button>
            <input type="number" min="8" max="72" :value="fontSizeValue()"
                @change="setFontSize(parseInt($event.target.value) || 16)" @mousedown.stop
                class="w-14 text-center text-sm rounded px-1 py-1 bg-transparent border border-(--border-color)"
                data-tip="Tamanho da fonte (px)">
            <button type="button" @mousedown.prevent @click="setFontSize(fontSizeValue() + 1)"
                class="w-7 h-7 flex items-center justify-center rounded cursor-pointer hover:bg-(--bg-hover) font-bold text-sm"
                data-tip="Aumentar fonte">+</button>
        </div>

        <div class="w-px h-6 bg-(--border-color) mx-1"></div>

        {{-- Formato (Parágrafo ▾): Headings / Inline / Blocks / Align / Styles --}}
        <div class="relative" @click.outside="closeFormatMenu()">
            <button type="button" @mousedown.prevent @click="toggleFormatMenu()"
                class="flex items-center gap-1.5 pl-2 pr-1.5 py-1.5 rounded cursor-pointer hover:bg-(--bg-hover) text-sm min-w-[112px]"
                data-tip="Formato do texto">
                <span class="flex-1 text-left" x-text="currentBlockLabel()"></span>
                <svg class="w-3.5 h-3.5 text-(--text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div x-show="formatMenuOpen" x-cloak
                class="absolute left-0 top-full mt-1 w-48 rounded-lg shadow-xl border border-(--border-color) bg-(--bg-surface) py-1 z-40">
                {{-- Headings --}}
                <div class="relative" @mouseenter="openSubmenu('headings')">
                    <button type="button" @mousedown.prevent
                        class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm hover:bg-(--bg-hover)">
                        Títulos
                        <svg class="w-3.5 h-3.5 text-(--text-muted)" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </button>
                    <div x-show="formatSubmenu === 'headings'" x-cloak
                        class="absolute left-full top-0 ml-1 w-52 rounded-lg shadow-xl border border-(--border-color) bg-(--bg-surface) py-1 z-50">
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().toggleHeading({ level: 1 }).run()); closeFormatMenu()"
                            :class="{ 'font-bold': isActive('heading', { level: 1 }) }"
                            class="w-full text-left px-3 py-1 hover:bg-(--bg-hover) text-2xl font-bold leading-tight">Título
                            1</button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().toggleHeading({ level: 2 }).run()); closeFormatMenu()"
                            :class="{ 'font-bold': isActive('heading', { level: 2 }) }"
                            class="w-full text-left px-3 py-1 hover:bg-(--bg-hover) text-xl font-bold leading-tight">Título
                            2</button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().toggleHeading({ level: 3 }).run()); closeFormatMenu()"
                            :class="{ 'font-bold': isActive('heading', { level: 3 }) }"
                            class="w-full text-left px-3 py-1 hover:bg-(--bg-hover) text-lg font-bold leading-tight">Título
                            3</button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().toggleHeading({ level: 4 }).run()); closeFormatMenu()"
                            :class="{ 'font-bold': isActive('heading', { level: 4 }) }"
                            class="w-full text-left px-3 py-1 hover:bg-(--bg-hover) text-base font-bold leading-tight">Título
                            4</button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().toggleHeading({ level: 5 }).run()); closeFormatMenu()"
                            :class="{ 'font-bold': isActive('heading', { level: 5 }) }"
                            class="w-full text-left px-3 py-1 hover:bg-(--bg-hover) text-sm font-bold leading-tight">Título
                            5</button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().toggleHeading({ level: 6 }).run()); closeFormatMenu()"
                            :class="{ 'font-bold': isActive('heading', { level: 6 }) }"
                            class="w-full text-left px-3 py-1 hover:bg-(--bg-hover) text-xs font-bold leading-tight">Título
                            6</button>
                    </div>
                </div>

                {{-- Inline --}}
                <div class="relative" @mouseenter="openSubmenu('inline')">
                    <button type="button" @mousedown.prevent
                        class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm hover:bg-(--bg-hover)">
                        Em linha
                        <svg class="w-3.5 h-3.5 text-(--text-muted)" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </button>
                    <div x-show="formatSubmenu === 'inline'" x-cloak
                        class="absolute left-full top-0 ml-1 w-44 rounded-lg shadow-xl border border-(--border-color) bg-(--bg-surface) py-1 z-50">
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().toggleBold().run()); closeFormatMenu()"
                            :class="{ 'font-semibold': isActive('bold') }"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm font-bold">Negrito</button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().toggleItalic().run()); closeFormatMenu()"
                            :class="{ 'font-semibold': isActive('italic') }"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm italic">Itálico</button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().toggleUnderline().run()); closeFormatMenu()"
                            :class="{ 'font-semibold': isActive('underline') }"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm underline">Sublinhado</button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().toggleStrike().run()); closeFormatMenu()"
                            :class="{ 'font-semibold': isActive('strike') }"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm line-through">Tachado</button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().toggleSuperscript().run()); closeFormatMenu()"
                            :class="{ 'font-semibold': isActive('superscript') }"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm">Sobrescrito</button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().toggleSubscript().run()); closeFormatMenu()"
                            :class="{ 'font-semibold': isActive('subscript') }"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm">Subscrito</button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().toggleCode().run()); closeFormatMenu()"
                            :class="{ 'font-semibold': isActive('code') }"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm font-mono">Código</button>
                    </div>
                </div>

                {{-- Blocks --}}
                <div class="relative" @mouseenter="openSubmenu('blocks')">
                    <button type="button" @mousedown.prevent
                        class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm hover:bg-(--bg-hover)">
                        Blocos
                        <svg class="w-3.5 h-3.5 text-(--text-muted)" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </button>
                    <div x-show="formatSubmenu === 'blocks'" x-cloak
                        class="absolute left-full top-0 ml-1 w-44 rounded-lg shadow-xl border border-(--border-color) bg-(--bg-surface) py-1 z-50">
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().setParagraph().run()); closeFormatMenu()"
                            class="w-full flex items-center justify-between gap-2 px-3 py-1.5 hover:bg-(--bg-hover) text-sm">
                            Parágrafo
                            <svg x-show="isActive('paragraph')" class="w-3.5 h-3.5"
                                style="color: var(--admin-primary, var(--brand-primary, #0C4A34));" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                        </button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().toggleBlockquote().run()); closeFormatMenu()"
                            class="w-full flex items-center justify-between gap-2 px-3 py-1.5 hover:bg-(--bg-hover) text-sm">
                            Citação
                            <svg x-show="isActive('blockquote')" class="w-3.5 h-3.5"
                                style="color: var(--admin-primary, var(--brand-primary, #0C4A34));" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                        </button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().setDiv().run()); closeFormatMenu()"
                            class="w-full flex items-center justify-between gap-2 px-3 py-1.5 hover:bg-(--bg-hover) text-sm">
                            Div
                            <svg x-show="isActive('div')" class="w-3.5 h-3.5"
                                style="color: var(--admin-primary, var(--brand-primary, #0C4A34));" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                        </button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().toggleCodeBlock().run()); closeFormatMenu()"
                            class="w-full flex items-center justify-between gap-2 px-3 py-1.5 hover:bg-(--bg-hover) text-sm">
                            Bloco de código
                            <svg x-show="isActive('codeBlock')" class="w-3.5 h-3.5"
                                style="color: var(--admin-primary, var(--brand-primary, #0C4A34));" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Align --}}
                <div class="relative" @mouseenter="openSubmenu('align')">
                    <button type="button" @mousedown.prevent
                        class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm hover:bg-(--bg-hover)">
                        Alinhar
                        <svg class="w-3.5 h-3.5 text-(--text-muted)" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </button>
                    <div x-show="formatSubmenu === 'align'" x-cloak
                        class="absolute left-full top-0 ml-1 w-36 rounded-lg shadow-xl border border-(--border-color) bg-(--bg-surface) py-1 z-50">
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().setTextAlign('left').run()); closeFormatMenu()"
                            :class="{ 'font-semibold': isActive({ textAlign: 'left' }) }"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm">Esquerda</button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().setTextAlign('center').run()); closeFormatMenu()"
                            :class="{ 'font-semibold': isActive({ textAlign: 'center' }) }"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm">Centro</button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().setTextAlign('right').run()); closeFormatMenu()"
                            :class="{ 'font-semibold': isActive({ textAlign: 'right' }) }"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm">Direita</button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().setTextAlign('justify').run()); closeFormatMenu()"
                            :class="{ 'font-semibold': isActive({ textAlign: 'justify' }) }"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm">Justificado</button>
                    </div>
                </div>

                {{-- Styles --}}
                <div class="relative" @mouseenter="openSubmenu('styles')">
                    <button type="button" @mousedown.prevent
                        class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm hover:bg-(--bg-hover)">
                        Estilos
                        <svg class="w-3.5 h-3.5 text-(--text-muted)" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </button>
                    <div x-show="formatSubmenu === 'styles'" x-cloak
                        class="absolute left-full top-0 ml-1 w-40 rounded-lg shadow-xl border border-(--border-color) bg-(--bg-surface) py-1 z-50">
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().toggleSmallCaps().run()); closeFormatMenu()"
                            :class="{ 'font-semibold': isActive('smallCaps') }"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm"
                            style="font-variant: small-caps;">Versalete</button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().toggleSubtitle().run()); closeFormatMenu()"
                            :class="{ 'font-semibold': isActive('subtitle') }"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm">Subtítulo</button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().toggleHighlight().run()); closeFormatMenu()"
                            :class="{ 'font-semibold': isActive('highlight') }"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm">Destaque</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Fonte --}}
        <select x-on:change="cmd(e => e.chain().setFontFamily($event.target.value).run())"
            class="p-2 rounded cursor-pointer text-sm bg-transparent hover:bg-(--bg-hover)" data-tip="Fonte">
            <option value="">Padrão</option>
            <option value="sans-serif" style="font-family: sans-serif;">Sans Serif</option>
            <option value="serif" style="font-family: serif;">Serif</option>
            <option value="monospace" style="font-family: monospace;">Monospace</option>
            <option value="cursive" style="font-family: cursive;">Cursiva</option>
        </select>

        <div class="w-px h-6 bg-(--border-color) mx-1"></div>

        {{-- Link --}}
        <button type="button" @mousedown.prevent @click="openLinkModal()"
            :class="{ 'bg-(--bg-hover)': isActive('link') }" class="p-2 rounded cursor-pointer hover:bg-(--bg-hover)"
            data-tip="Inserir/editar link (Ctrl+K)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                </path>
            </svg>
        </button>

        {{-- Imagem: insere a caixa "Drop files here" (ImagePlaceholder),
        que escolhe arquivo ou aceita uma URL na própria barrinha --}}
        <button type="button" @mousedown.prevent @click="insertImagePlaceholder()"
            class="p-2 rounded cursor-pointer hover:bg-(--bg-hover)" data-tip="Inserir imagem">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                </path>
            </svg>
        </button>

        {{-- Tabela --}}
        <div class="relative" @click.outside="closeTableMenu()">
            <button type="button" @mousedown.prevent @click="toggleTableMenu()"
                class="flex items-center gap-0.5 p-2 rounded cursor-pointer hover:bg-(--bg-hover)" data-tip="Tabela">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5zM3 10h18M3 15h18M9 3v18">
                    </path>
                </svg>
                <svg class="w-3 h-3 text-(--text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div x-show="tableMenuOpen" x-cloak
                class="absolute left-0 top-full mt-1 w-44 rounded-lg shadow-xl border border-(--border-color) bg-(--bg-surface) py-1 z-40">
                {{-- Table: grade de tamanho --}}
                <div class="relative" @mouseenter="openTableSubmenu('table')">
                    <button type="button" @mousedown.prevent
                        class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm hover:bg-(--bg-hover)">
                        Tabela
                        <svg class="w-3.5 h-3.5 text-(--text-muted)" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </button>
                    <div x-show="tableSubmenu === 'table'" x-cloak
                        class="absolute left-full top-0 ml-1 p-2 w-max rounded-lg shadow-xl border border-(--border-color) bg-(--bg-surface) z-50"
                        @mouseleave="gridHoverRows = 0; gridHoverCols = 0">
                        <div class="grid grid-cols-10 gap-0.5">
                            <template x-for="r in 10" :key="r">
                                <template x-for="c in 10" :key="c">
                                    <div class="w-4 h-4 border cursor-pointer"
                                        :class="(r <= gridHoverRows && c <= gridHoverCols) ? 'bg-blue-500 border-blue-600' : 'bg-(--bg-elevated) border-(--border-color)'"
                                        @mouseenter="gridHoverRows = r; gridHoverCols = c"
                                        @click="insertTableGrid(r, c)"></div>
                                </template>
                            </template>
                        </div>
                        <div class="text-center text-xs text-(--text-muted) mt-1"
                            x-text="`${gridHoverRows}x${gridHoverCols}`"></div>
                    </div>
                </div>

                {{-- Cell --}}
                <div class="relative" @mouseenter="insideTable() && openTableSubmenu('cell')">
                    <button type="button" @mousedown.prevent :disabled="!insideTable()"
                        class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm"
                        :class="insideTable() ? 'hover:bg-(--bg-hover)' : 'opacity-40 cursor-not-allowed'">
                        Célula
                        <svg class="w-3.5 h-3.5 text-(--text-muted)" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </button>
                    <div x-show="tableSubmenu === 'cell' && insideTable()" x-cloak
                        class="absolute left-full top-0 ml-1 w-40 rounded-lg shadow-xl border border-(--border-color) bg-(--bg-surface) py-1 z-50">
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().mergeCells().run()); closeTableMenu()"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm">Mesclar células</button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().splitCell().run()); closeTableMenu()"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm">Dividir célula</button>
                    </div>
                </div>

                {{-- Row --}}
                <div class="relative" @mouseenter="insideTable() && openTableSubmenu('row')">
                    <button type="button" @mousedown.prevent :disabled="!insideTable()"
                        class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm"
                        :class="insideTable() ? 'hover:bg-(--bg-hover)' : 'opacity-40 cursor-not-allowed'">
                        Linha
                        <svg class="w-3.5 h-3.5 text-(--text-muted)" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </button>
                    <div x-show="tableSubmenu === 'row' && insideTable()" x-cloak
                        class="absolute left-full top-0 ml-1 w-44 rounded-lg shadow-xl border border-(--border-color) bg-(--bg-surface) py-1 z-50">
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().addRowBefore().run()); closeTableMenu()"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm">Inserir linha
                            antes</button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().addRowAfter().run()); closeTableMenu()"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm">Inserir linha
                            depois</button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().deleteRow().run()); closeTableMenu()"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm">Excluir linha</button>
                    </div>
                </div>

                {{-- Column --}}
                <div class="relative" @mouseenter="insideTable() && openTableSubmenu('column')">
                    <button type="button" @mousedown.prevent :disabled="!insideTable()"
                        class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm"
                        :class="insideTable() ? 'hover:bg-(--bg-hover)' : 'opacity-40 cursor-not-allowed'">
                        Coluna
                        <svg class="w-3.5 h-3.5 text-(--text-muted)" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </button>
                    <div x-show="tableSubmenu === 'column' && insideTable()" x-cloak
                        class="absolute left-full top-0 ml-1 w-48 rounded-lg shadow-xl border border-(--border-color) bg-(--bg-surface) py-1 z-50">
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().addColumnBefore().run()); closeTableMenu()"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm">Inserir coluna
                            antes</button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().addColumnAfter().run()); closeTableMenu()"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm">Inserir coluna
                            depois</button>
                        <button type="button" @mousedown.prevent
                            @click="cmd(e => e.chain().deleteColumn().run()); closeTableMenu()"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm">Excluir coluna</button>
                    </div>
                </div>

                {{-- Sort --}}
                <div class="relative" @mouseenter="insideTable() && openTableSubmenu('sort')">
                    <button type="button" @mousedown.prevent :disabled="!insideTable()"
                        class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm"
                        :class="insideTable() ? 'hover:bg-(--bg-hover)' : 'opacity-40 cursor-not-allowed'">
                        Ordenar
                        <svg class="w-3.5 h-3.5 text-(--text-muted)" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </button>
                    <div x-show="tableSubmenu === 'sort' && insideTable()" x-cloak
                        class="absolute left-full top-0 ml-1 w-56 rounded-lg shadow-xl border border-(--border-color) bg-(--bg-surface) py-1 z-50">
                        <button type="button" @mousedown.prevent @click="sortTableColumn('asc')"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm">Ordenar tabela
                            (crescente)</button>
                        <button type="button" @mousedown.prevent @click="sortTableColumn('desc')"
                            class="w-full text-left px-3 py-1.5 hover:bg-(--bg-hover) text-sm">Ordenar tabela
                            (decrescente)</button>
                    </div>
                </div>

                <div class="border-t border-(--border-color) my-1"></div>

                <button type="button" @mousedown.prevent :disabled="!insideTable()" @click="openTablePropertiesModal()"
                    class="w-full text-left px-3 py-1.5 text-sm"
                    :class="insideTable() ? 'hover:bg-(--bg-hover)' : 'opacity-40 cursor-not-allowed'">Propriedades da
                    tabela</button>
                <button type="button" @mousedown.prevent :disabled="!insideTable()"
                    @click="cmd(e => e.chain().deleteTable().run()); closeTableMenu()"
                    class="w-full text-left px-3 py-1.5 text-sm"
                    :class="insideTable() ? 'hover:bg-(--bg-hover)' : 'opacity-40 cursor-not-allowed'">Excluir
                    tabela</button>
            </div>
        </div>

        <div class="w-px h-6 bg-(--border-color) mx-1"></div>

        {{-- Align: dropdown com Left/Center/Right/Justify (igual TinyMCE) --}}
        <div class="relative" @click.outside="closeAlignMenu()">
            <button type="button" @mousedown.prevent @click="toggleAlignMenu()"
                class="flex items-center gap-0.5 p-2 rounded cursor-pointer hover:bg-(--bg-hover)"
                data-tip="Alinhamento">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M3 12h12M3 18h16">
                    </path>
                </svg>
                <svg class="w-3 h-3 text-(--text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div x-show="alignMenuOpen" x-cloak
                class="absolute left-0 top-full mt-1 w-36 rounded-lg shadow-xl border border-(--border-color) bg-(--bg-surface) py-1 z-40">
                <button type="button" @mousedown.prevent
                    @click="cmd(e => e.chain().setTextAlign('left').run()); closeAlignMenu()"
                    class="w-full flex items-center gap-2 px-3 py-1.5 text-sm"
                    :class="isActive({ textAlign: 'left' }) ? 'bg-(--bg-hover) font-medium' : 'hover:bg-(--bg-hover)'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 6h18M3 12h12M3 18h16"></path>
                    </svg>
                    Esquerda
                </button>
                <button type="button" @mousedown.prevent
                    @click="cmd(e => e.chain().setTextAlign('center').run()); closeAlignMenu()"
                    class="w-full flex items-center gap-2 px-3 py-1.5 text-sm"
                    :class="isActive({ textAlign: 'center' }) ? 'bg-(--bg-hover) font-medium' : 'hover:bg-(--bg-hover)'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M8 12h8M4 18h12"></path>
                    </svg>
                    Centro
                </button>
                <button type="button" @mousedown.prevent
                    @click="cmd(e => e.chain().setTextAlign('right').run()); closeAlignMenu()"
                    class="w-full flex items-center gap-2 px-3 py-1.5 text-sm"
                    :class="isActive({ textAlign: 'right' }) ? 'bg-(--bg-hover) font-medium' : 'hover:bg-(--bg-hover)'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 6h18M9 12h12M5 18h16"></path>
                    </svg>
                    Direita
                </button>
                <button type="button" @mousedown.prevent
                    @click="cmd(e => e.chain().setTextAlign('justify').run()); closeAlignMenu()"
                    class="w-full flex items-center gap-2 px-3 py-1.5 text-sm"
                    :class="isActive({ textAlign: 'justify' }) ? 'bg-(--bg-hover) font-medium' : 'hover:bg-(--bg-hover)'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 6h18M3 12h18M3 18h18"></path>
                    </svg>
                    Justificado
                </button>
            </div>
        </div>

        {{-- Bullet List: ícone liga/desliga a lista, a setinha abre a
        grade de estilos (list-style-type) igual ao TinyMCE --}}
        <div class="relative flex items-center" @click.outside="closeBulletListMenu()">
            <button type="button" @mousedown.prevent @click="cmd(e => e.chain().toggleBulletList().run())"
                :class="{ 'bg-(--bg-hover)': isActive('bulletList') }"
                class="p-2 rounded-l cursor-pointer hover:bg-(--bg-hover)" data-tip="Lista com marcadores">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"></path>
                </svg>
            </button>
            <button type="button" @mousedown.prevent @click="toggleBulletListMenu()"
                class="self-stretch px-0.5 rounded-r cursor-pointer hover:bg-(--bg-hover)" data-tip="Estilos de lista">
                <svg class="w-3 h-3 text-(--text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div x-show="bulletListMenuOpen" x-cloak
                class="editor-toolbar absolute left-0 top-full mt-1 p-2 w-max rounded-lg shadow-xl border border-(--border-color) bg-(--bg-surface) z-40">
                <div class="grid grid-cols-2 gap-1">
                    <button type="button" @mousedown.prevent @click="setBulletListStyle(null)" data-tip="Padrão"
                        class="p-2 rounded border hover:bg-(--bg-hover)"
                        :class="isActive('bulletList', { listStyleType: null }) ? 'border-[color:var(--admin-primary,var(--brand-primary,#0C4A34))]' : 'border-(--border-color)'">
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">•</span>
                                <div class="h-1 w-7 rounded bg-(--text-muted)"></div>
                            </div>
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">•</span>
                                <div class="h-1 w-7 rounded bg-(--text-muted)"></div>
                            </div>
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">•</span>
                                <div class="h-1 w-5 rounded bg-(--text-muted)"></div>
                            </div>
                        </div>
                    </button>
                    <button type="button" @mousedown.prevent @click="setBulletListStyle('circle')" data-tip="Círculo"
                        class="p-2 rounded border hover:bg-(--bg-hover)"
                        :class="isActive('bulletList', { listStyleType: 'circle' }) ? 'border-[color:var(--admin-primary,var(--brand-primary,#0C4A34))]' : 'border-(--border-color)'">
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">○</span>
                                <div class="h-1 w-7 rounded bg-(--text-muted)"></div>
                            </div>
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">○</span>
                                <div class="h-1 w-7 rounded bg-(--text-muted)"></div>
                            </div>
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">○</span>
                                <div class="h-1 w-5 rounded bg-(--text-muted)"></div>
                            </div>
                        </div>
                    </button>
                    <button type="button" @mousedown.prevent @click="setBulletListStyle('disc')" data-tip="Disco"
                        class="p-2 rounded border hover:bg-(--bg-hover)"
                        :class="isActive('bulletList', { listStyleType: 'disc' }) ? 'border-[color:var(--admin-primary,var(--brand-primary,#0C4A34))]' : 'border-(--border-color)'">
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">•</span>
                                <div class="h-1 w-7 rounded bg-(--text-muted)"></div>
                            </div>
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">•</span>
                                <div class="h-1 w-7 rounded bg-(--text-muted)"></div>
                            </div>
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">•</span>
                                <div class="h-1 w-5 rounded bg-(--text-muted)"></div>
                            </div>
                        </div>
                    </button>
                    <button type="button" @mousedown.prevent @click="setBulletListStyle('square')" data-tip="Quadrado"
                        class="p-2 rounded border hover:bg-(--bg-hover)"
                        :class="isActive('bulletList', { listStyleType: 'square' }) ? 'border-[color:var(--admin-primary,var(--brand-primary,#0C4A34))]' : 'border-(--border-color)'">
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">▪</span>
                                <div class="h-1 w-7 rounded bg-(--text-muted)"></div>
                            </div>
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">▪</span>
                                <div class="h-1 w-7 rounded bg-(--text-muted)"></div>
                            </div>
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">▪</span>
                                <div class="h-1 w-5 rounded bg-(--text-muted)"></div>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        {{-- Ordered List: mesmo padrão do Bullet List, mas com a grade de
        estilos 1,2,3 / a,b,c / A,B,C / i,ii,iii / α,β,γ / I,II,III --}}
        <div class="relative flex items-center" @click.outside="closeOrderedListMenu()">
            <button type="button" @mousedown.prevent @click="cmd(e => e.chain().toggleOrderedList().run())"
                :class="{ 'bg-(--bg-hover)': isActive('orderedList') }"
                class="p-2 rounded-l cursor-pointer hover:bg-(--bg-hover)" data-tip="Lista numerada">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 6h13M7 12h13M7 18h13M3 6h.01M3 12h.01M3 18h.01"></path>
                </svg>
            </button>
            <button type="button" @mousedown.prevent @click="toggleOrderedListMenu()"
                class="self-stretch px-0.5 rounded-r cursor-pointer hover:bg-(--bg-hover)" data-tip="Estilos de lista">
                <svg class="w-3 h-3 text-(--text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div x-show="orderedListMenuOpen" x-cloak
                class="editor-toolbar absolute left-0 top-full mt-1 p-2 w-max rounded-lg shadow-xl border border-(--border-color) bg-(--bg-surface) z-40">
                <div class="grid grid-cols-3 gap-1">
                    <button type="button" @mousedown.prevent @click="setOrderedListStyle(null)" data-tip="Padrão"
                        class="p-2 rounded border hover:bg-(--bg-hover)"
                        :class="isActive('orderedList', { listStyleType: null }) ? 'border-[color:var(--admin-primary,var(--brand-primary,#0C4A34))]' : 'border-(--border-color)'">
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">1.</span>
                                <div class="h-1 w-6 rounded bg-(--text-muted)"></div>
                            </div>
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">2.</span>
                                <div class="h-1 w-6 rounded bg-(--text-muted)"></div>
                            </div>
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">3.</span>
                                <div class="h-1 w-4 rounded bg-(--text-muted)"></div>
                            </div>
                        </div>
                    </button>
                    <button type="button" @mousedown.prevent @click="setOrderedListStyle('lower-alpha')"
                        data-tip="Letras minúsculas" class="p-2 rounded border hover:bg-(--bg-hover)"
                        :class="isActive('orderedList', { listStyleType: 'lower-alpha' }) ? 'border-[color:var(--admin-primary,var(--brand-primary,#0C4A34))]' : 'border-(--border-color)'">
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">a.</span>
                                <div class="h-1 w-6 rounded bg-(--text-muted)"></div>
                            </div>
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">b.</span>
                                <div class="h-1 w-6 rounded bg-(--text-muted)"></div>
                            </div>
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">c.</span>
                                <div class="h-1 w-4 rounded bg-(--text-muted)"></div>
                            </div>
                        </div>
                    </button>
                    <button type="button" @mousedown.prevent @click="setOrderedListStyle('upper-alpha')"
                        data-tip="Letras maiúsculas" class="p-2 rounded border hover:bg-(--bg-hover)"
                        :class="isActive('orderedList', { listStyleType: 'upper-alpha' }) ? 'border-[color:var(--admin-primary,var(--brand-primary,#0C4A34))]' : 'border-(--border-color)'">
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">A.</span>
                                <div class="h-1 w-6 rounded bg-(--text-muted)"></div>
                            </div>
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">B.</span>
                                <div class="h-1 w-6 rounded bg-(--text-muted)"></div>
                            </div>
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">C.</span>
                                <div class="h-1 w-4 rounded bg-(--text-muted)"></div>
                            </div>
                        </div>
                    </button>
                    <button type="button" @mousedown.prevent @click="setOrderedListStyle('lower-roman')"
                        data-tip="Romanos minúsculos" class="p-2 rounded border hover:bg-(--bg-hover)"
                        :class="isActive('orderedList', { listStyleType: 'lower-roman' }) ? 'border-[color:var(--admin-primary,var(--brand-primary,#0C4A34))]' : 'border-(--border-color)'">
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">i.</span>
                                <div class="h-1 w-6 rounded bg-(--text-muted)"></div>
                            </div>
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">ii.</span>
                                <div class="h-1 w-6 rounded bg-(--text-muted)"></div>
                            </div>
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">iii.</span>
                                <div class="h-1 w-4 rounded bg-(--text-muted)"></div>
                            </div>
                        </div>
                    </button>
                    <button type="button" @mousedown.prevent @click="setOrderedListStyle('lower-greek')"
                        data-tip="Letras gregas" class="p-2 rounded border hover:bg-(--bg-hover)"
                        :class="isActive('orderedList', { listStyleType: 'lower-greek' }) ? 'border-[color:var(--admin-primary,var(--brand-primary,#0C4A34))]' : 'border-(--border-color)'">
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">α.</span>
                                <div class="h-1 w-6 rounded bg-(--text-muted)"></div>
                            </div>
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">β.</span>
                                <div class="h-1 w-6 rounded bg-(--text-muted)"></div>
                            </div>
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">γ.</span>
                                <div class="h-1 w-4 rounded bg-(--text-muted)"></div>
                            </div>
                        </div>
                    </button>
                    <button type="button" @mousedown.prevent @click="setOrderedListStyle('upper-roman')"
                        data-tip="Romanos maiúsculos" class="p-2 rounded border hover:bg-(--bg-hover)"
                        :class="isActive('orderedList', { listStyleType: 'upper-roman' }) ? 'border-[color:var(--admin-primary,var(--brand-primary,#0C4A34))]' : 'border-(--border-color)'">
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">I.</span>
                                <div class="h-1 w-6 rounded bg-(--text-muted)"></div>
                            </div>
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">II.</span>
                                <div class="h-1 w-6 rounded bg-(--text-muted)"></div>
                            </div>
                            <div class="flex items-center gap-1"><span class="text-[10px] leading-none">III.</span>
                                <div class="h-1 w-4 rounded bg-(--text-muted)"></div>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        {{-- Checklist --}}
        <button type="button" @mousedown.prevent @click="toggleTaskList()"
            :class="{ 'bg-(--bg-hover)': isActive('taskList') }"
            class="p-2 rounded cursor-pointer hover:bg-(--bg-hover)" data-tip="Lista de tarefas">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 11l3 3L22 4M3 11.5V19a2 2 0 002 2h14a2 2 0 002-2v-7.5M9 3H5a2 2 0 00-2 2v4"></path>
            </svg>
        </button>

        <div class="w-px h-6 bg-(--border-color) mx-1"></div>

        {{-- Blockquote --}}
        <button type="button" @mousedown.prevent @click="cmd(e => e.chain().toggleBlockquote().run())"
            :class="{ 'bg-(--bg-hover)': isActive('blockquote') }"
            class="p-2 rounded cursor-pointer hover:bg-(--bg-hover)" data-tip="Citação">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                </path>
            </svg>
        </button>

        {{-- Code Block --}}
        <button type="button" @mousedown.prevent @click="cmd(e => e.chain().toggleCodeBlock().run())"
            :class="{ 'bg-(--bg-hover)': isActive('codeBlock') }"
            class="p-2 rounded cursor-pointer hover:bg-(--bg-hover)" data-tip="Bloco de código">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
            </svg>
        </button>

        {{-- Horizontal Rule --}}
        <button type="button" @mousedown.prevent @click="cmd(e => e.chain().setHorizontalRule().run())"
            class="p-2 rounded cursor-pointer hover:bg-(--bg-hover)" data-tip="Linha horizontal">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
            </svg>
        </button>

        <div class="w-px h-6 bg-(--border-color) mx-1"></div>

        {{-- Remover formatação --}}
        <button type="button" @mousedown.prevent @click="clearFormatting()"
            class="p-2 rounded cursor-pointer hover:bg-(--bg-hover)" data-tip="Remover formatação">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 7V4h16v3M9 20h6M12 4L8 20m8-13l-6 6"></path>
            </svg>
        </button>

        <div class="w-px h-6 bg-(--border-color) mx-1"></div>

        {{-- Ver código HTML --}}
        <button type="button" @mousedown.prevent @click="openCodeView()"
            class="p-2 rounded cursor-pointer hover:bg-(--bg-hover)" data-tip="Ver/editar HTML">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4-4 4M7 8l-4 4 4 4">
                </path>
            </svg>
        </button>

        {{-- Tela cheia --}}
        <button type="button" @mousedown.prevent @click="toggleFullscreen()" :class="{ 'bg-(--bg-hover)': fullscreen }"
            class="p-2 rounded cursor-pointer hover:bg-(--bg-hover)" data-tip="Tela cheia">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path x-show="!fullscreen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 8V4h4M16 4h4v4M20 16v4h-4M8 20H4v-4"></path>
                <path x-show="fullscreen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 4v4H5M15 4v4h4M15 20v-4h4M9 20v-4H5"></path>
            </svg>
        </button>
        {{-- Undo / Redo --}}
        <button type="button" @mousedown.prevent @click="cmd(e => e.chain().undo().run())" :disabled="!can('undo')"
            class="p-2 rounded cursor-pointer hover:bg-(--bg-hover) disabled:cursor-not-allowed disabled:opacity-50"
            data-tip="Desfazer (Ctrl+Z)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
            </svg>
        </button>
        <button type="button" @mousedown.prevent @click="cmd(e => e.chain().redo().run())" :disabled="!can('redo')"
            class="p-2 rounded cursor-pointer hover:bg-(--bg-hover) disabled:cursor-not-allowed disabled:opacity-50"
            data-tip="Refazer (Ctrl+Shift+Z)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 10H11a8 8 0 00-8 8v2m18-10l-6 6m6-6l-6-6"></path>
            </svg>
        </button>
    </div>

    {{-- Barra de progresso da imagem inserida no corpo (ImagePlaceholder ou
    aba "Upload" do modal de link) — "{{ $uploadModel }}" é a mesma
    propriedade do input de arquivo escondido abaixo. --}}
    <x-ui.upload-progress :target="$uploadModel" class="px-3 py-1.5 border-b border-(--border-color)" />

    {{-- Editor --}}
    <div x-ref="editor"
        class="prose prose-sm sm:prose max-w-none sm:max-w-none p-4 focus:outline-none dark:prose-invert overflow-y-auto"
        :style="fullscreen ? 'min-height: 0; height: calc(100vh - 50px);' : 'min-height: {{ $height }};'"></div>

    {{-- Hidden Input --}}
    <input type="hidden" name="{{ $name }}" x-model="content">

    {{-- Único input de arquivo real do componente: nunca aparece na tela,
    é disparado via $refs.imageInput.click() pelo ImagePlaceholder e
    pela aba "Upload" do modal de link (ver comentário no topo). --}}
    <input type="file" accept="image/*" x-ref="imageInput" wire:model="{{ $uploadModel }}" class="hidden">

    {{-- Modal "Ver código" (igual TinyMCE: realce de sintaxe, números de
    linha, copiar/formatar/claro-escuro/fonte, cancelar ou salvar) --}}
    <div x-show="codeViewOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        style="background-color: rgba(0, 0, 0, .5);">
        <div @click.outside="cancelCodeView()" :class="codeViewFullscreen ? 'w-full h-full' : 'w-full max-w-5xl'"
            class="rounded-lg shadow-2xl overflow-hidden flex flex-col bg-(--bg-surface)"
            :style="codeViewFullscreen ? '' : 'height: 80vh;'">
            {{-- Barra de ferramentas do modal --}}
            <div
                class="editor-toolbar flex items-center gap-1 px-3 py-2 bg-(--bg-elevated) border-b border-(--border-color)">
                <button type="button" @click="codeViewFullscreen = !codeViewFullscreen"
                    class="p-1.5 rounded text-(--text-secondary) hover:bg-(--bg-hover) hover:text-(--text-primary)"
                    data-tip="Tela cheia">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 8V4h4M16 4h4v4M20 16v4h-4M8 20H4v-4"></path>
                    </svg>
                </button>

                <div class="w-px h-6 bg-(--border-color) mx-1"></div>

                <button type="button" @click="copyCode()"
                    class="flex items-center gap-1.5 px-2 py-1.5 rounded text-(--text-secondary) hover:bg-(--bg-hover) hover:text-(--text-primary) text-xs font-semibold"
                    data-tip="Copiar código">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                        </path>
                    </svg>
                    <span x-text="codeCopied ? 'Copiado!' : 'Copiar código'"></span>
                </button>

                <button type="button" @click="formatCode()"
                    class="flex items-center gap-1.5 px-2 py-1.5 rounded text-(--text-secondary) hover:bg-(--bg-hover) hover:text-(--text-primary) text-xs font-semibold"
                    data-tip="Formatar código">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h10M4 18h16"></path>
                    </svg>
                    Formatar código
                </button>

                <div class="flex-1"></div>

                <button type="button" @click="codeFontSize = Math.min(24, codeFontSize + 1)"
                    class="w-7 h-7 flex items-center justify-center rounded text-(--text-secondary) hover:bg-(--bg-hover) hover:text-(--text-primary) font-bold text-sm"
                    data-tip="Aumentar fonte">T+</button>
                <button type="button" @click="codeFontSize = Math.max(10, codeFontSize - 1)"
                    class="w-7 h-7 flex items-center justify-center rounded text-(--text-secondary) hover:bg-(--bg-hover) hover:text-(--text-primary) font-bold text-sm"
                    data-tip="Diminuir fonte">T−</button>

                <div class="w-px h-6 bg-(--border-color) mx-1"></div>

                <x-ui.button type="button" variant="ghost" size="sm" @click="cancelCodeView()">Cancelar</x-ui.button>
                <x-ui.button type="button" size="sm" @click="saveCodeView()">Salvar código</x-ui.button>
            </div>

            {{-- Área de código: números de linha + realce de sintaxe + textarea transparente por cima --}}
            <div class="flex-1 overflow-auto bg-(--bg-surface)">
                <div class="flex" style="min-height: 100%;">
                    <div class="select-none text-right pr-3 pl-4 sticky left-0 bg-(--bg-surface) text-(--text-muted)"
                        :style="`font-family: ui-monospace, monospace; font-size: ${codeFontSize}px; line-height: 1.6; padding-top: 1rem;`">
                        <template x-for="n in codeViewLines()" :key="n">
                            <div x-text="n"></div>
                        </template>
                    </div>

                    <div class="relative flex-1">
                        <pre class="absolute inset-0 m-0 p-4 pointer-events-none overflow-hidden text-(--text-primary)"
                            :style="`white-space: pre; font-family: ui-monospace, monospace; font-size: ${codeFontSize}px; line-height: 1.6;`"
                            x-html="highlightedCodeView()"></pre>
                        <textarea x-model="codeViewContent" spellcheck="false"
                            class="relative w-full h-full p-4 bg-transparent resize-none outline-none caret-(--text-primary)"
                            :style="`white-space: pre; font-family: ui-monospace, monospace; color: transparent; font-size: ${codeFontSize}px; line-height: 1.6; min-height: ${codeViewLines() * codeFontSize * 1.6}px;`"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Color Picker (texto/destaque): quadrado de saturação/valor + barra
    de matiz + R/G/B/hex — igual ao seletor de cor do Google Docs. --}}
    <div x-show="colorModalOpen" x-cloak class="fixed inset-0 z-110 flex items-center justify-center p-4"
        style="background-color: rgba(0, 0, 0, .5);">
        <div @click.outside="closeColorModal()" class="w-full max-w-md rounded-lg shadow-2xl bg-(--bg-surface) p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-(--text-primary)">Seletor de cor</h3>
                <button type="button" @click="closeColorModal()"
                    class="p-1 rounded hover:bg-(--bg-hover) text-(--text-secondary)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <div class="flex gap-4">
                {{-- Quadrado de saturação (x) / valor (y) --}}
                <div x-ref="colorSquare" class="relative w-52 h-52 rounded cursor-crosshair shrink-0"
                    :style="`background-color: hsl(${colorModalHue}, 100%, 50%); background-image: linear-gradient(to top, #000, transparent), linear-gradient(to right, #fff, transparent);`"
                    @mousedown="colorModalDragging = 'square'; updateSquareFromEvent($event)"
                    @mousemove.window="colorModalDragging === 'square' && updateSquareFromEvent($event)"
                    @mouseup.window="colorModalDragging = null">
                    <div class="absolute w-4 h-4 rounded-full border-2 border-white shadow -translate-x-1/2 -translate-y-1/2 pointer-events-none"
                        :style="`left: ${colorModalSat}%; top: ${100 - colorModalVal}%; background-color: ${colorModalHex};`">
                    </div>
                </div>

                {{-- Barra de matiz (hue) --}}
                <div x-ref="colorHueSlider" class="relative w-5 h-52 rounded cursor-pointer shrink-0"
                    style="background: linear-gradient(to bottom, red, yellow, lime, cyan, blue, magenta, red);"
                    @mousedown="colorModalDragging = 'hue'; updateHueFromEvent($event)"
                    @mousemove.window="colorModalDragging === 'hue' && updateHueFromEvent($event)"
                    @mouseup.window="colorModalDragging = null">
                    <div class="absolute -left-0.5 -right-0.5 h-1.5 rounded border-2 border-white shadow -translate-y-1/2 pointer-events-none"
                        :style="`top: ${(colorModalHue / 360) * 100}%;`"></div>
                </div>

                {{-- R/G/B + hex --}}
                <div class="flex-1 space-y-2 min-w-0">
                    <label class="flex items-center gap-2 text-sm text-(--text-secondary)">
                        <span class="w-4">R</span>
                        <input type="number" min="0" max="255" x-model.number="colorModalR"
                            @change="syncColorModalFromRgb()"
                            class="w-full rounded px-2 py-1 border border-(--border-color) bg-transparent text-(--text-primary)">
                    </label>
                    <label class="flex items-center gap-2 text-sm text-(--text-secondary)">
                        <span class="w-4">G</span>
                        <input type="number" min="0" max="255" x-model.number="colorModalG"
                            @change="syncColorModalFromRgb()"
                            class="w-full rounded px-2 py-1 border border-(--border-color) bg-transparent text-(--text-primary)">
                    </label>
                    <label class="flex items-center gap-2 text-sm text-(--text-secondary)">
                        <span class="w-4">B</span>
                        <input type="number" min="0" max="255" x-model.number="colorModalB"
                            @change="syncColorModalFromRgb()"
                            class="w-full rounded px-2 py-1 border border-(--border-color) bg-transparent text-(--text-primary)">
                    </label>
                    <label class="flex items-center gap-2 text-sm text-(--text-secondary)">
                        <span class="w-4">#</span>
                        <input type="text" x-model="colorModalHex" @change="syncColorModalFromHex()"
                            class="w-full rounded px-2 py-1 border border-(--border-color) bg-transparent text-(--text-primary) font-mono">
                    </label>
                    <div class="w-full h-8 rounded border border-(--border-color)"
                        :style="`background-color: ${colorModalHex};`"></div>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <x-ui.button type="button" variant="ghost" size="sm" @click="closeColorModal()">Cancelar</x-ui.button>
                <x-ui.button type="button" size="sm" @click="saveColorModal()">Salvar</x-ui.button>
            </div>
        </div>
    </div>

    {{-- Insert/Edit Link --}}
    <div x-show="linkModalOpen" x-cloak class="fixed inset-0 z-110 flex items-center justify-center p-4"
        style="background-color: rgba(0, 0, 0, .5);">
        <div @click.outside="closeLinkModal()" class="w-full max-w-lg rounded-lg shadow-2xl bg-(--bg-surface) p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-lg text-(--text-primary)">Inserir/editar link</h3>
                <button type="button" @click="closeLinkModal()"
                    class="p-1 rounded hover:bg-(--bg-hover) text-(--text-secondary)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <div class="flex gap-6">
                {{-- Abas --}}
                <div class="flex flex-col gap-2 w-20 shrink-0 text-sm pt-1">
                    <button type="button" @click="linkModalTab = 'general'"
                        :class="linkModalTab === 'general' ? 'text-blue-600 underline font-medium' : 'text-(--text-secondary) hover:text-(--text-primary)'"
                        class="text-left">Geral</button>
                    <button type="button" @click="linkModalTab = 'upload'"
                        :class="linkModalTab === 'upload' ? 'text-blue-600 underline font-medium' : 'text-(--text-secondary) hover:text-(--text-primary)'"
                        class="text-left">Enviar arquivo</button>
                </div>

                {{-- General --}}
                <div class="flex-1 min-w-0">
                    <div x-show="linkModalTab === 'general'" class="space-y-3">
                        <label class="block text-sm">
                            <span class="block mb-1 text-(--text-secondary)">URL</span>
                            <input type="text" x-model="linkModalUrl" placeholder="https://"
                                class="w-full rounded px-3 py-2 border border-(--border-color) bg-transparent text-(--text-primary)">
                        </label>
                        <label class="block text-sm">
                            <span class="block mb-1 text-(--text-secondary)">Texto a exibir</span>
                            <input type="text" x-model="linkModalText"
                                class="w-full rounded px-3 py-2 border border-(--border-color) bg-transparent text-(--text-primary)">
                        </label>
                        <label class="block text-sm">
                            <span class="block mb-1 text-(--text-secondary)">Título</span>
                            <input type="text" x-model="linkModalTitle"
                                class="w-full rounded px-3 py-2 border border-(--border-color) bg-transparent text-(--text-primary)">
                        </label>
                        <label class="block text-sm">
                            <span class="block mb-1 text-(--text-secondary)">Abrir link em...</span>
                            <select x-model="linkModalTarget"
                                class="w-full rounded px-3 py-2 border border-(--border-color) bg-transparent text-(--text-primary)">
                                <option value="_self">Janela atual</option>
                                <option value="_blank">Nova janela</option>
                            </select>
                        </label>
                    </div>

                    {{-- Upload: reaproveita o mesmo wire:model do botão "Inserir
                    imagem" da toolbar — ver comentário no topo do arquivo. --}}
                    <div x-show="linkModalTab === 'upload'">
                        <button type="button" @click="linkModalUploadPending = true; $refs.imageInput.click()"
                            class="w-full flex flex-col items-center justify-center gap-2 h-40 rounded border-2 border-dashed border-(--border-color) cursor-pointer hover:bg-(--bg-hover) text-(--text-secondary)">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 20h16a2 2 0 002-2V6a2 2 0 00-2-2H4a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                            <span class="text-sm"
                                x-text="linkModalUploadPending ? 'Enviando...' : 'Clique para enviar um arquivo'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <x-ui.button type="button" variant="ghost" size="sm" @click="closeLinkModal()">Cancelar</x-ui.button>
                <x-ui.button type="button" size="sm" @click="saveLinkModal()">Salvar</x-ui.button>
            </div>
        </div>
    </div>

    {{-- Table properties --}}
    <div x-show="tablePropertiesOpen" x-cloak class="fixed inset-0 z-110 flex items-center justify-center p-4"
        style="background-color: rgba(0, 0, 0, .5);">
        <div @click.outside="closeTablePropertiesModal()"
            class="w-full max-w-sm rounded-lg shadow-2xl bg-(--bg-surface) p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-lg text-(--text-primary)">Propriedades da tabela</h3>
                <button type="button" @click="closeTablePropertiesModal()"
                    class="p-1 rounded hover:bg-(--bg-hover) text-(--text-secondary)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <div class="space-y-3">
                <label class="block text-sm">
                    <span class="block mb-1 text-(--text-secondary)">Largura</span>
                    <input type="text" x-model="tablePropertiesWidth" placeholder="100%"
                        class="w-full rounded px-3 py-2 border border-(--border-color) bg-transparent text-(--text-primary)">
                </label>
                <label class="block text-sm">
                    <span class="block mb-1 text-(--text-secondary)">Alinhamento</span>
                    <select x-model="tablePropertiesAlign"
                        class="w-full rounded px-3 py-2 border border-(--border-color) bg-transparent text-(--text-primary)">
                        <option value="">Padrão</option>
                        <option value="left">Esquerda</option>
                        <option value="center">Centro</option>
                        <option value="right">Direita</option>
                    </select>
                </label>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <x-ui.button type="button" variant="ghost" size="sm"
                    @click="closeTablePropertiesModal()">Cancelar</x-ui.button>
                <x-ui.button type="button" size="sm" @click="saveTableProperties()">Salvar</x-ui.button>
            </div>
        </div>
    </div>
</div>