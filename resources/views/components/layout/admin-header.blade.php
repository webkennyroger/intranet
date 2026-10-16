{{--
    Cabeçalho fixo do painel admin. Tem o botão que alterna o menu lateral
    (colapsa no desktop, abre/fecha off-canvas no mobile), uma busca
    (ainda desabilitada) e o menu do usuário com link de volta à intranet
    e logout.
--}}
<header class="sticky top-0 z-45 lg:z-30 h-16 flex items-center justify-between px-4 gap-4 shadow-sm"
    style="background-color: var(--admin-header); color: var(--admin-header-text); border-bottom: 1px solid var(--border-color);">
    <div class="flex items-center gap-3 min-w-0">
        <x-ui.tooltip text="Alternar menu lateral" position="bottom">
            <button
                @click="window.innerWidth >= 1024 ? (sidebarCollapsed = !sidebarCollapsed) : (mobileSidebarOpen = !mobileSidebarOpen)"
                class="p-2 rounded-lg hover:bg-black/10 transition" aria-label="Alternar menu lateral">
                <i class="fa-solid fa-bars text-lg"></i>
            </button>
        </x-ui.tooltip>
    </div>

    <div class="hidden md:flex items-center flex-1 max-w-md">
        <div class="relative w-full">
            <i class="fa-solid fa-magnifying-glass text-sm absolute left-3 top-1/2 -translate-y-1/2 opacity-60"></i>
            <input type="text" disabled placeholder="Buscar..."
                class="w-full h-10 pl-9 pr-4 rounded-[10px] bg-black/5 border border-black/10 text-sm placeholder-current/50 focus:outline-none"
                style="color: inherit;" />
        </div>
    </div>

    <div class="flex items-center gap-2" x-data="{ userMenuOpen: false }">
        <x-ui.theme-toggle class="relative p-2 rounded-full hover:bg-black/10 transition" style="color: inherit;" />



        <div class="h-6 w-px bg-current opacity-20 hidden sm:block"></div>

        <div class="relative">
            <button @click="userMenuOpen = !userMenuOpen"
                class="flex items-center gap-2.5 cursor-pointer p-1 rounded-lg hover:bg-black/10 transition">
                <div class="w-8 h-8 rounded-full overflow-hidden flex items-center justify-center text-white font-bold text-xs shrink-0"
                    style="background-color: var(--admin-primary);">
                    {{ auth()->user()->getAvatarInitials() }}
                </div>
                <div class="hidden sm:block text-left leading-tight">
                    <span class="block text-sm font-semibold">{{ auth()->user()->name }}</span>
                    <span
                        class="block text-xs opacity-70">{{ auth()->user()->getRoleNames()->first() ?? 'Sem papel' }}</span>
                </div>
                <i class="fa-solid fa-chevron-down text-[10px] opacity-60"></i>
            </button>

            <div x-show="userMenuOpen" @click.outside="userMenuOpen = false" x-cloak
                class="absolute right-0 mt-2 w-56 rounded-xl shadow-lg overflow-hidden bg-white dark:bg-(--bg-surface) border border-transparent dark:border-(--border-color) text-slate-700 dark:text-(--text-primary) z-50">
                <div class="px-4 py-3 border-b border-slate-100 dark:border-(--border-color)">
                    <span class="block text-sm font-semibold">{{ auth()->user()->name }}</span>
                    <span
                        class="block text-xs text-slate-400 dark:text-(--text-secondary) truncate">{{ auth()->user()->email }}</span>
                </div>

                <div class="py-1">
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-600 dark:text-(--text-secondary) hover:bg-emerald-50 dark:hover:bg-(--bg-hover) hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors">
                        <i class="fa-solid fa-arrow-left w-4 text-center"></i> Voltar à intranet
                    </a>
                </div>

                <div class="border-t border-slate-100 dark:border-(--border-color) py-1">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors">
                            <i class="fa-solid fa-right-from-bracket w-4 text-center"></i> Sair
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>