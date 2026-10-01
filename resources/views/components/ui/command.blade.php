@props([
    'items' => [],
    'shortcut' => 'k',
])

{{--
    Paleta de comandos "Cmd+K". Abre com o atalho Cmd/Ctrl+{shortcut}, com um
    <x-ui.icon> ou slot de trigger opcional, filtra os itens conforme o usuário
    digita e permite navegar com as setas + Enter (Esc fecha).

    Cada item de $items aceita: 'label' (obrigatório), 'icon' (nome pra
    <x-ui.icon>), 'kbd' (atalho exibido à direita), 'href' (navega ao
    executar) e/ou 'action' (dispara um CustomEvent no window com esse nome,
    pra quem estiver ouvindo via `x-on:{action}.window` ou Livewire#dispatch
    decidir o que fazer — o componente em si não conhece regra de negócio).
--}}

<div
    x-data="{
        open: false,
        query: '',
        activeIndex: 0,
        hasResults: {{ count($items) > 0 ? 'true' : 'false' }},
        visible() {
            return Array.from(this.$refs.commandList ? this.$refs.commandList.children : []).filter((el) => el.style.display !== 'none');
        },
        highlight() {
            this.visible().forEach((el, index) => {
                el.classList.toggle('bg-[var(--bg-hover)]', index === this.activeIndex);
            });
        },
        onQueryChange() {
            this.$nextTick(() => {
                const items = this.visible();
                this.hasResults = items.length > 0;
                this.activeIndex = 0;
                this.highlight();
            });
        },
        move(step) {
            const items = this.visible();
            if (! items.length) return;
            this.activeIndex = (this.activeIndex + step + items.length) % items.length;
            this.highlight();
        },
        runActive() {
            const items = this.visible();
            const el = items[this.activeIndex];
            if (el) el.click();
        },
    }"
    x-init="$watch('query', () => onQueryChange()); $watch('open', (value) => { if (value) { query = ''; activeIndex = 0; $nextTick(() => { onQueryChange(); $refs.commandSearchInput?.focus(); }); } })"
    x-on:keydown.window="if ((($event.metaKey || $event.ctrlKey) && $event.key.toLowerCase() === '{{ $shortcut }}')) { $event.preventDefault(); open = true; }"
    x-on:keydown.escape.window="open = false"
    {{ $attributes }}
>
    {{-- Trigger opcional (ex.: botão visível "Buscar... ⌘K") --}}
    @isset($trigger)
        <div @click="open = true">
            {{ $trigger }}
        </div>
    @endisset

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-modal="true"
        role="dialog"
    >
        <!-- Backdrop -->
        <div
            class="fixed inset-0 backdrop-blur-sm"
            style="background-color: rgba(0, 0, 0, 0.5);"
            @click="open = false"
        ></div>

        <!-- Painel -->
        <div class="flex min-h-full items-start justify-center p-4" style="padding-top: 15vh;">
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.outside="open = false"
                class="w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden"
                style="background-color: var(--bg-surface); border: 1px solid var(--border-color);"
            >
                <!-- Campo de busca -->
                <div class="flex items-center gap-2 px-4 py-3" style="border-bottom: 1px solid var(--border-color);">
                    <x-ui.icon name="search" class="w-4 h-4 shrink-0" style="color: var(--text-muted);" />
                    <input
                        type="text"
                        x-ref="commandSearchInput"
                        x-model="query"
                        x-on:keydown.arrow-down.prevent="move(1)"
                        x-on:keydown.arrow-up.prevent="move(-1)"
                        x-on:keydown.enter.prevent="runActive()"
                        placeholder="Digite um comando ou busque..."
                        autocomplete="off"
                        class="w-full bg-transparent focus:outline-none text-sm"
                        style="color: var(--text-primary);"
                    />
                    <kbd class="text-xs px-1.5 py-0.5 rounded-md shrink-0" style="background-color: var(--bg-elevated); color: var(--text-muted); border: 1px solid var(--border-color);">Esc</kbd>
                </div>

                <!-- Lista de comandos -->
                <div class="max-h-80 overflow-y-auto py-1.5" x-ref="commandList">
                    @foreach($items as $item)
                        @php
                            $label = $item['label'] ?? '';
                            $icon = $item['icon'] ?? null;
                            $kbd = $item['kbd'] ?? null;
                            $href = $item['href'] ?? null;
                            $action = $item['action'] ?? null;
                        @endphp

                        <button
                            type="button"
                            data-command-item
                            data-label="{{ strtolower($label) }}"
                            x-show="query === '' || $el.dataset.label.includes(query.toLowerCase())"
                            @click="open = false; @if($href) window.location.href = '{{ $href }}'; @elseif($action) window.dispatchEvent(new CustomEvent('{{ $action }}')); @endif"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-left text-sm transition-colors duration-100"
                            style="color: var(--text-primary);"
                        >
                            @if($icon)
                                <x-ui.icon :name="$icon" class="w-4 h-4 shrink-0" style="color: var(--text-muted);" />
                            @endif
                            <span class="flex-1 truncate">{{ $label }}</span>
                            @if($kbd)
                                <kbd class="text-xs px-1.5 py-0.5 rounded-md shrink-0" style="background-color: var(--bg-elevated); color: var(--text-muted); border: 1px solid var(--border-color);">{{ $kbd }}</kbd>
                            @endif
                        </button>
                    @endforeach
                </div>

                <p x-show="!hasResults" x-cloak class="px-4 py-6 text-sm text-center" style="color: var(--text-muted);">
                    Nenhum resultado encontrado.
                </p>
            </div>
        </div>
    </div>
</div>
