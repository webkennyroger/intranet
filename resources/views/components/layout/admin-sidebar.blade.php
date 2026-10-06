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
        'lg:w-20': sidebarCollapsed,
        'lg:w-64': !sidebarCollapsed,
    }"
    class="w-64 fixed inset-y-0 left-0 z-50 transition-all duration-300 ease-in-out border-r border-black/10 flex flex-col shrink-0 overflow-y-auto"
>
    <button
        @click="mobileSidebarOpen = false"
        class="lg:hidden absolute top-3 right-3 w-8 h-8 rounded-full bg-black/5 hover:bg-black/10 flex items-center justify-center"
        aria-label="Fechar menu"
    >
        <i class="fa-solid fa-xmark"></i>
    </button>

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
                $isSubmenuGroup = in_array($section['group'] ?? null, ['CONTEÚDO', 'DEFENSORES', 'GESTÃO', 'OUTROS', 'ADMINISTRAÇÃO'], true);
            @endphp

            @if ($items->isNotEmpty())
                @if ($isSubmenuGroup)
                    <div x-data="{ open: {{ ($section['defaultOpen'] ?? false) ? 'true' : 'false' }} }">
                        <button
                            type="button"
                            @click="open = !open"
                            class="relative w-full flex flex-row items-center gap-3 pl-3 pr-3 py-2.5 rounded-xl font-medium transition hover:bg-black/5 text-slate-600 dark:text-[#8aa39a]"
                            :class="sidebarCollapsed ? 'lg:flex-col lg:gap-0.5 lg:justify-center lg:px-1 lg:py-2.5' : ''"
                        >
                            <i class="fa-solid fa-{{ $section['icon'] }} w-4 text-center text-slate-400 dark:text-[#70847d]"></i>
                            <span class="flex-1 text-left" :class="sidebarCollapsed ? 'lg:text-[9px] lg:text-center lg:leading-tight' : ''">{{ $section['group'] }}</span>
                            <i
                                class="fa-solid text-[10px] text-slate-400 dark:text-[#70847d]"
                                :class="open ? 'fa-minus' : 'fa-plus'"
                                x-show="!sidebarCollapsed"
                            ></i>
                        </button>

                        <div x-show="open" x-cloak class="pl-5 mt-0.5 space-y-0.5" :class="sidebarCollapsed ? 'lg:hidden' : ''">
                            @foreach ($items as $item)
                                @php $isActive = ! ($item['alias'] ?? false) && request()->routeIs($item['route']); @endphp
                                <a
                                    href="{{ route($item['route']) }}"
                                    class="relative flex items-center gap-2.5 pl-4 pr-3 py-2 rounded-lg transition text-xs {{ $isActive ? 'font-semibold' : 'font-medium text-slate-500 dark:text-[#70847d] hover:bg-black/5' }}"
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
                                :class="sidebarCollapsed ? 'lg:flex-col lg:gap-0.5 lg:justify-center lg:px-1 lg:py-2.5' : ''"
                                class="relative flex flex-row items-center gap-3 pl-3 py-2.5 transition {{ $isActive ? 'font-bold -mr-3 pr-6 rounded-l-xl' : 'font-medium pr-3 rounded-xl hover:bg-black/5' }}"
                                @if ($isActive) style="background-color: var(--admin-sidebar-active-bg); color: var(--admin-sidebar-active-text);" @endif
                            >
                                <span
                                    class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ $isActive ? '' : 'bg-black/5' }}"
                                    @if ($isActive) style="background-color: color-mix(in srgb, var(--admin-sidebar-active-text) 18%, transparent);" @endif
                                >
                                    <i
                                        class="fa-solid fa-{{ $item['icon'] }} text-sm {{ $isActive ? '' : 'text-slate-400 dark:text-[#70847d]' }}"
                                        @if ($isActive) style="color: var(--admin-sidebar-active-text);" @endif
                                    ></i>
                                </span>
                                <span class="flex-1 {{ $isActive ? '' : 'text-slate-600 dark:text-[#8aa39a]' }}" :class="sidebarCollapsed ? 'lg:text-[9px] lg:text-center lg:leading-tight' : ''">{{ $item['label'] }}</span>
                                @if ($isActive)
                                    <span class="absolute right-0.5 inset-y-0 w-1" style="background-color: var(--admin-sidebar-active-text);"></span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endif
            @endif
        @endforeach
    </nav>
</aside>
