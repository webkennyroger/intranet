{{--
    Menu lateral do site público, montado a partir das seções e itens
    definidos em config/sidebar.php. Cada item pode ter um "permission"
    que filtra sua exibição conforme o usuário logado. sidebarCollapsed
    controla o modo recolhido no desktop e mobileSidebarOpen controla o
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
    class="sidebar-card w-64 fixed inset-y-0 left-0 z-50 lg:top-16 transition-all duration-300 ease-in-out bg-white border-r border-slate-200 flex flex-col shrink-0 overflow-y-auto"
>
    <button
        @click="mobileSidebarOpen = false"
        class="sidebar-muted-bg lg:hidden absolute top-3 right-3 w-8 h-8 rounded bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 dark:text-(--text-secondary)"
        aria-label="Fechar menu"
    >
        <i class="fa-solid fa-xmark"></i>
    </button>

    <div class="sidebar-subtle-bg p-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50" :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''">
        <div class="sidebar-muted-bg w-12 h-12 rounded bg-slate-200 shrink-0 flex items-center justify-center overflow-hidden border border-slate-300">
            <i class="fa-solid fa-user-tie text-2xl text-slate-500 dark:text-(--text-secondary)"></i>
        </div>
        <div class="overflow-hidden" :class="sidebarCollapsed ? 'lg:hidden' : ''">
            <p class="text-xs text-slate-500 dark:text-(--text-secondary) font-medium">Bem-vindo,</p>
            <p class="text-sm font-bold truncate" style="color: var(--brand-primary-dark, #0C4A34);">{{ auth()->user()->name }}</p>
        </div>
    </div>

    <nav class="py-3 space-y-6 text-sm" :class="sidebarCollapsed ? 'lg:space-y-3' : ''">
        @foreach (config('sidebar') as $section)
            @php
                $items = collect($section['items'] ?? [$section])
                    ->filter(fn ($item) => $item['permission'] === null || auth()->user()->can($item['permission']));
            @endphp

            @if ($items->isNotEmpty())
                <div>
                    @if (isset($section['group']))
                        <h3 class="px-3 text-[11px] font-bold text-slate-400 dark:text-(--text-muted) tracking-wider uppercase mb-1" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ $section['group'] }}</h3>
                    @endif
                    <ul class="space-y-0.5">
                        @foreach ($items as $item)
                            @php $isActive = request()->routeIs($item['route']); @endphp
                            <li class="relative">
                                @if ($isActive)
                                    <span class="absolute left-0 top-1.5 bottom-1.5 w-1 rounded" style="background-color: var(--brand-primary);"></span>
                                @endif
                                <a
                                    href="{{ route($item['route']) }}"
                                    :class="sidebarCollapsed ? 'lg:flex-col lg:gap-1 lg:justify-center lg:px-2 lg:py-2 lg:text-center' : ''"
                                    class="{{ $isActive ? 'sidebar-muted-bg' : 'sidebar-nav-item' }} flex flex-row items-center gap-3 px-3 py-2 rounded font-medium transition {{ $isActive ? 'font-semibold' : 'text-slate-600 hover:bg-slate-100 dark:text-(--text-primary)' }}"
                                    @if ($isActive) style="background-color: var(--sidebar-active-bg); color: var(--brand-primary-dark);" @endif
                                >
                                    <i
                                        class="fa-solid fa-{{ $item['icon'] }} text-center shrink-0 {{ $isActive ? '' : 'text-slate-400 dark:text-(--text-secondary)' }}"
                                        :class="sidebarCollapsed ? '' : 'w-4'"
                                        @if ($isActive) style="color: var(--brand-primary-dark);" @endif
                                    ></i>
                                    <span :class="sidebarCollapsed ? 'lg:text-[11px] lg:leading-tight' : ''">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    </nav>

    <div class="sidebar-border-t p-3 border-t border-slate-200 hidden lg:block">
        <button
            @click="sidebarCollapsed = !sidebarCollapsed"
            class="sidebar-nav-item w-full flex items-center justify-center gap-2 px-3 py-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 dark:text-(--text-secondary) dark:hover:text-(--text-primary) rounded text-sm font-medium transition"
            :class="sidebarCollapsed ? '' : 'justify-between'"
        >
            <span class="flex items-center gap-2">
                <i class="fa-solid" :class="sidebarCollapsed ? 'fa-angle-double-right' : 'fa-angle-double-left'"></i>
                <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Recolher menu</span>
            </span>
        </button>
    </div>
</aside>
