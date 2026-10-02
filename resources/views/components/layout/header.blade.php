<header class="bg-dpemt-dark text-white sticky top-0 z-50 shadow-md h-16 flex items-center justify-between px-4" style="background-color: #0C4A34;">
    <div class="flex items-center gap-3">
        <button id="sidebar-toggle" @click="mobileSidebarOpen = true" class="lg:hidden p-2 rounded-lg hover:bg-emerald-800/60 transition text-white/90">
            <i class="fa-solid fa-bars text-lg"></i>
        </button>

        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-full bg-white/10 border border-white/20 flex items-center justify-center p-1">
                <svg class="w-full h-full text-white fill-current" viewBox="0 0 24 24">
                    <path d="M12 2L3 7v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-5.45 9-12V7l-9-5zm0 2.18l7 3.89v5.03c0 4.54-3.12 8.78-7 9.87-3.88-1.09-7-5.33-7-9.87V8.07l7-3.89zM11 7v2h2V7h-2zm0 4v6h2v-6h-2z"/>
                </svg>
            </div>
            <div>
                <h1 class="font-extrabold text-sm uppercase tracking-wider leading-tight text-white">Intranet DPEMT</h1>
                <p class="text-[10px] text-emerald-200 uppercase tracking-widest font-medium">Defensoria Pública do Estado de Mato Grosso</p>
            </div>
        </div>
    </div>

    <div class="hidden md:flex items-center flex-1 max-w-md mx-8">
        <div class="relative w-full">
            <input type="text" placeholder="Buscar no sistema..."
                class="w-full py-1.5 pl-4 pr-10 rounded-full bg-emerald-950/60 border border-emerald-700/50 text-white placeholder-emerald-300/70 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400" />
            <button class="absolute right-3 top-1/2 -translate-y-1/2 text-emerald-300 hover:text-white">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </button>
        </div>
    </div>

    <div class="flex items-center gap-4" x-data="{ userMenuOpen: false }">
        <button class="relative p-2 rounded-full hover:bg-emerald-800/60 text-emerald-100 transition">
            <i class="fa-regular fa-bell text-lg"></i>
        </button>

        <div class="h-6 w-px bg-emerald-800 hidden sm:block"></div>

        <div class="relative">
            <button @click="userMenuOpen = !userMenuOpen" class="flex items-center gap-2.5 cursor-pointer p-1 rounded-lg hover:bg-emerald-800/40 transition">
                <div class="w-8 h-8 rounded-full bg-emerald-700 overflow-hidden border border-emerald-400 flex items-center justify-center text-white font-bold text-xs">
                    {{ auth()->user()->getAvatarInitials() }}
                </div>
                <div class="hidden sm:block text-left">
                    <span class="text-xs font-semibold text-white block leading-tight">{{ auth()->user()->name }}</span>
                </div>
                <i class="fa-solid fa-chevron-down text-[10px] text-emerald-300"></i>
            </button>

            <div x-show="userMenuOpen" @click.outside="userMenuOpen = false" x-cloak
                class="absolute right-0 mt-2 w-48 rounded-xl shadow-lg bg-white text-slate-700 py-1 z-50">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm hover:bg-slate-100">Sair</button>
                </form>
            </div>
        </div>
    </div>
</header>
