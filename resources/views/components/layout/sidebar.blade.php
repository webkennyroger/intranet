<aside
    x-cloak
    :class="{
        'translate-x-0': mobileSidebarOpen,
        '-translate-x-full lg:translate-x-0': !mobileSidebarOpen,
        'lg:w-20': sidebarCollapsed,
        'lg:w-64': !sidebarCollapsed,
    }"
    class="w-64 fixed inset-y-0 left-0 z-50 lg:static lg:z-auto transition-all duration-300 ease-in-out bg-white border-r border-slate-200 flex flex-col shrink-0 overflow-y-auto"
>
    <button
        @click="mobileSidebarOpen = false"
        class="lg:hidden absolute top-3 right-3 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500"
        aria-label="Fechar menu"
    >
        <i class="fa-solid fa-xmark"></i>
    </button>

    <div class="p-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50" :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''">
        <div class="w-12 h-12 rounded-full bg-slate-200 flex-shrink-0 flex items-center justify-center overflow-hidden border border-slate-300">
            <i class="fa-solid fa-user-tie text-2xl text-slate-500"></i>
        </div>
        <div class="overflow-hidden" :class="sidebarCollapsed ? 'lg:hidden' : ''">
            <p class="text-xs text-slate-500 font-medium">Bem-vindo,</p>
            <p class="text-sm font-bold text-slate-800 truncate">{{ auth()->user()->name }}</p>
            <span class="inline-block mt-0.5 px-2 py-0.5 bg-slate-200/80 text-slate-600 text-[10px] font-semibold rounded-full">
                {{ auth()->user()->getRoleNames()->first() ?? 'Sem papel' }}
            </span>
        </div>
    </div>

    <nav class="p-3 space-y-6 flex-1 text-sm">
        @foreach (config('sidebar') as $section)
            @php
                $items = collect($section['items'] ?? [$section])
                    ->filter(fn ($item) => $item['permission'] === null || auth()->user()->can($item['permission']));
            @endphp

            @if ($items->isNotEmpty())
                <div>
                    @if (isset($section['group']))
                        <h3 class="px-3 text-[11px] font-bold text-slate-400 tracking-wider uppercase mb-1" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ $section['group'] }}</h3>
                    @endif
                    <ul class="space-y-0.5">
                        @foreach ($items as $item)
                            @php $isActive = request()->routeIs($item['route']); @endphp
                            <li class="relative {{ $isActive ? "before:content-[''] before:absolute before:left-0 before:top-1.5 before:bottom-1.5 before:w-1 before:bg-emerald-600 before:rounded-r" : '' }}">
                                <a
                                    href="{{ route($item['route']) }}"
                                    :class="sidebarCollapsed ? 'lg:flex-col lg:gap-0.5 lg:justify-center lg:px-1 lg:py-2.5' : ''"
                                    class="flex flex-row items-center gap-3 px-3 py-2 rounded-lg font-medium transition {{ $isActive ? 'bg-emerald-50 text-emerald-800 font-semibold' : 'text-slate-600 hover:bg-slate-100' }}"
                                >
                                    <i class="fa-solid fa-{{ $item['icon'] }} w-4 text-center {{ $isActive ? 'text-emerald-700' : 'text-slate-400' }}"></i>
                                    <span :class="sidebarCollapsed ? 'lg:text-[9px] lg:text-center lg:leading-tight' : ''">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    </nav>

    <div class="p-3 border-t border-slate-200 hidden lg:block">
        <button
            @click="sidebarCollapsed = !sidebarCollapsed"
            class="w-full flex items-center justify-center gap-2 px-3 py-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg text-sm font-medium transition"
            :class="sidebarCollapsed ? '' : 'justify-between'"
        >
            <span class="flex items-center gap-2">
                <i class="fa-solid" :class="sidebarCollapsed ? 'fa-angle-double-right' : 'fa-angle-double-left'"></i>
                <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Recolher menu</span>
            </span>
        </button>
    </div>
</aside>
