{{--
    Menu lateral do painel admin, montado a partir de config/admin_sidebar.php.
    Seções cujo "group" é CONTEÚDO, DEFENSORES, GESTÃO, OUTROS ou
    ADMINISTRAÇÃO são renderizadas como grupos expansíveis (acordeão via
    Alpine); as demais aparecem como links diretos. Cada item pode ter um
    "permission" (só aparece se o usuário tiver a permissão) e um "alias"
    que marca a rota como variante de outra, evitando que o item fique
    marcado como ativo quando a rota compartilhada já destacou outro item.
    sidebarCollapsed/mobileSidebarOpen controlam o colapso no desktop e o
    off-canvas no mobile.
--}}
<aside
    x-cloak
    :class="{
        'translate-x-0': mobileSidebarOpen,
        '-translate-x-full lg:translate-x-0': !mobileSidebarOpen,
        'lg:w-26': sidebarCollapsed,
        'lg:w-64': !sidebarCollapsed,
    }"
    class="w-64 fixed inset-y-0 left-0 z-50 transition-all duration-300 ease-in-out border-r border-black/10 flex flex-col shrink-0 overflow-y-auto"
>
    <div class="lg:hidden absolute top-3 right-3">
        <x-ui.tooltip text="Fechar menu" position="left">
            <button
                @click="mobileSidebarOpen = false"
                class="w-8 h-8 rounded-full bg-black/5 hover:bg-black/10 flex items-center justify-center"
                aria-label="Fechar menu"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>
        </x-ui.tooltip>
    </div>

    <a href="{{ route('dashboard') }}"
        class="flex items-center h-16 px-4 shrink-0 gap-2.5 overflow-hidden border-b border-black/10"
        style="background-color: var(--admin-nav-header); color: var(--admin-nav-header-text);"
    >
        <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0" style="background-color: var(--admin-primary-light);">
            <i class="fa-solid fa-user-shield" style="color: var(--admin-primary);"></i>
        </div>
        <span class="font-extrabold text-sm uppercase tracking-wider whitespace-nowrap pl-3"
            style="border-left: 1px solid var(--border-color);"
            :class="sidebarCollapsed ? 'lg:hidden' : ''">
            Painel Admin
        </span>
    </a>

    <nav class="p-3 space-y-1 flex-1 overflow-y-auto text-sm" style="background-color: var(--admin-sidebar); color: var(--admin-sidebar-text);">
        @foreach (config('admin_sidebar') as $section)
            @php
                $items = collect($section['items'] ?? [$section])
                    ->filter(fn ($item) => $item['permission'] === null || auth()->user()->can($item['permission']));
                $isSubmenuGroup = in_array($section['group'] ?? null, ['CONTEÚDO', 'DEFENSORES', 'OUTROS', 'ADMINISTRAÇÃO'], true);
            @endphp

            @if ($items->isNotEmpty())
                @if ($isSubmenuGroup)
                    <div
                        x-data="{
                            open: {{ ($section['defaultOpen'] ?? false) ? 'true' : 'false' }},
                            flyoutOpen: false,
                            flyoutTop: 0,
                            closeTimer: null,
                            openFlyout() {
                                clearTimeout(this.closeTimer);
                                this.flyoutTop = this.$refs.groupTrigger.getBoundingClientRect().top;
                                this.flyoutOpen = true;
                            },
                            scheduleClose() {
                                clearTimeout(this.closeTimer);
                                this.closeTimer = setTimeout(() => { this.flyoutOpen = false; }, 200);
                            },
                            toggle() {
                                if (sidebarCollapsed) {
                                    this.flyoutOpen ? (this.flyoutOpen = false) : this.openFlyout();
                                } else {
                                    this.open = !this.open;
                                }
                            },
                            handleOutsideClick(event) {
                                if (this.flyoutOpen && !this.$refs.groupTrigger.contains(event.target) && !this.$refs.groupFlyout.contains(event.target)) {
                                    this.flyoutOpen = false;
                                }
                            },
                        }"
                        @click.window="handleOutsideClick($event)"
                        @keydown.escape.window="flyoutOpen = false"
                        x-init="$watch('sidebarCollapsed', () => flyoutOpen = false)"
                    >
                        <button
                            type="button"
                            x-ref="groupTrigger"
                            @click="toggle()"
                            @mouseenter="sidebarCollapsed && openFlyout()"
                            @mouseleave="sidebarCollapsed && scheduleClose()"
                            class="relative w-full flex flex-row items-center gap-3 pl-3 pr-3 py-2.5 rounded-xl font-medium transition hover:bg-black/5"
                            :class="sidebarCollapsed ? 'lg:flex-col lg:gap-0.5 lg:justify-center lg:px-1 lg:py-2.5' : ''"
                        >
                            <i class="fa-solid fa-{{ $section['icon'] }} w-4 text-center opacity-70"></i>
                            <span class="flex-1 text-left" :class="sidebarCollapsed ? 'lg:text-[9px] lg:text-center lg:leading-tight' : ''">{{ $section['group'] }}</span>
                            <i
                                class="fa-solid text-[10px] opacity-70"
                                :class="open ? 'fa-minus' : 'fa-plus'"
                                x-show="!sidebarCollapsed"
                            ></i>
                        </button>

                        <!-- Acordeão inline: sidebar expandida -->
                        <div x-show="open" x-cloak class="pl-5 mt-0.5 space-y-0.5" :class="sidebarCollapsed ? 'lg:hidden' : ''">
                            @foreach ($items as $item)
                                @php $isActive = ! ($item['alias'] ?? false) && request()->routeIs($item['route']); @endphp
                                <a
                                    href="{{ route($item['route']) }}"
                                    class="relative flex items-center gap-2.5 pl-4 pr-3 py-2 rounded-lg transition text-xs {{ $isActive ? 'font-semibold' : 'font-medium opacity-80 hover:bg-black/5' }}"
                                    @if ($isActive)
                                        style="color: var(--admin-sidebar-active-text); background-color: var(--admin-sidebar-active-bg);"
                                    @else
                                        onmouseover="this.querySelectorAll('.sidebar-tree-marker').forEach(el => el.style.backgroundColor = 'var(--admin-primary)')"
                                        onmouseout="this.querySelectorAll('.sidebar-tree-marker').forEach(el => el.style.backgroundColor = 'var(--border-color)')"
                                    @endif
                                >
                                    <span class="sidebar-tree-marker absolute left-0 inset-y-0 w-0.5" style="background-color: {{ $isActive ? 'var(--admin-sidebar-active-text)' : 'var(--border-color)' }};"></span>
                                    <span class="sidebar-tree-marker absolute left-0 top-1/2 -translate-y-1/2 w-2.5 h-0.5" style="background-color: {{ $isActive ? 'var(--admin-sidebar-active-text)' : 'var(--border-color)' }};"></span>
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>

                        <!-- Flyout: sidebar colapsada (desktop) — o acordeão acima fica
                             escondido nesse estado, então o submenu abre do lado. -->
                        <template x-teleport="body">
                            <div
                                x-ref="groupFlyout"
                                x-show="flyoutOpen"
                                x-cloak
                                @mouseenter="clearTimeout(closeTimer)"
                                @mouseleave="scheduleClose()"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                :style="`top: ${flyoutTop}px; left: 112px; background-color: var(--bg-surface); border: 1px solid var(--border-color);`"
                                class="fixed z-50 w-56 rounded-xl shadow-xl py-2"
                                role="menu"
                            >
                                <div class="flex items-center gap-2 px-4 pb-2 text-[10px] font-bold uppercase tracking-wider" style="color: var(--text-muted);">
                                    <i class="fa-solid fa-{{ $section['icon'] }}"></i> {{ $section['group'] }}
                                </div>
                                @foreach ($items as $item)
                                    @php $isActive = ! ($item['alias'] ?? false) && request()->routeIs($item['route']); @endphp
                                    <a
                                        href="{{ route($item['route']) }}"
                                        @click="flyoutOpen = false"
                                        class="block px-4 py-2 text-sm transition {{ $isActive ? 'font-semibold' : 'font-medium hover:bg-(--bg-hover)' }}"
                                        style="{{ $isActive ? 'color: var(--admin-sidebar-active-text); background-color: var(--admin-sidebar-active-bg);' : 'color: var(--text-secondary);' }}"
                                    >
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </template>
                    </div>
                @else
                    <div class="space-y-0.5">
                        @if (isset($section['group']))
                            <div class="px-3 pb-1 text-[10px] font-semibold uppercase tracking-wider opacity-50" :class="sidebarCollapsed ? 'lg:hidden' : ''">
                                {{ $section['group'] }}
                            </div>
                        @endif

                        @foreach ($items as $item)
                            @php $isActive = request()->routeIs($item['route']); @endphp
                            <a
                                href="{{ route($item['route']) }}"
                                x-data="{ showTip: false, tipTop: 0 }"
                                @mouseenter="if (sidebarCollapsed) { tipTop = $el.getBoundingClientRect().top + $el.getBoundingClientRect().height / 2; showTip = true; }"
                                @mouseleave="showTip = false"
                                :class="sidebarCollapsed ? 'lg:flex-col lg:gap-0.5 lg:justify-center lg:px-1 lg:py-2.5' : ''"
                                class="relative flex flex-row items-center gap-3 pl-3 py-2.5 transition {{ $isActive ? 'font-bold -mr-3 pr-6 rounded-l-xl' : 'font-medium pr-3 rounded-xl hover:bg-black/5' }}"
                                @if ($isActive) style="background-color: var(--admin-sidebar-active-bg); color: var(--admin-sidebar-active-text);" @endif
                            >
                                <span
                                    class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ $isActive ? '' : 'bg-black/5' }}"
                                    @if ($isActive) style="background-color: color-mix(in srgb, var(--admin-sidebar-active-text) 18%, transparent);" @endif
                                >
                                    <i
                                        class="fa-solid fa-{{ $item['icon'] }} text-sm {{ $isActive ? '' : 'opacity-70' }}"
                                        @if ($isActive) style="color: var(--admin-sidebar-active-text);" @endif
                                    ></i>
                                </span>
                                <span class="flex-1 {{ $isActive ? '' : 'opacity-80' }}" :class="sidebarCollapsed ? 'lg:text-[9px] lg:text-center lg:leading-tight' : ''">{{ $item['label'] }}</span>
                                @if ($isActive)
                                    <span class="absolute right-0.5 inset-y-0 w-1" style="background-color: var(--admin-sidebar-active-text);"></span>
                                @endif

                                <!-- Tooltip: só aparece com a sidebar colapsada (desktop), já que o rótulo fica
                                     escondido — teleportado pro body porque a <aside> tem overflow-y-auto, que
                                     cortaria qualquer coisa posicionada normalmente alem da borda da sidebar. -->
                                <template x-teleport="body">
                                    <div
                                        x-show="showTip"
                                        x-cloak
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        :style="`top: ${tipTop}px; left: 112px;`"
                                        class="fixed -translate-y-1/2 z-50 whitespace-nowrap px-2.5 py-1.5 rounded-lg text-xs font-medium shadow-lg pointer-events-none bg-slate-900 text-white dark:bg-white dark:text-slate-900"
                                        role="tooltip"
                                    >
                                        {{ $item['label'] }}
                                    </div>
                                </template>
                            </a>
                        @endforeach
                    </div>
                @endif
            @endif
        @endforeach
    </nav>
</aside>
