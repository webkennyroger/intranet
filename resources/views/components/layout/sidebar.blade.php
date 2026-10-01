<aside x-show="sidebarOpen" id="sidebar" class="w-64 bg-white border-r border-slate-200 flex flex-col shrink-0 overflow-y-auto">
    <div class="p-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
        <div class="w-12 h-12 rounded-full bg-slate-200 flex-shrink-0 flex items-center justify-center overflow-hidden border border-slate-300">
            <i class="fa-solid fa-user-tie text-2xl text-slate-500"></i>
        </div>
        <div class="overflow-hidden">
            <p class="text-xs text-slate-500 font-medium">Bem-vindo,</p>
            <p class="text-sm font-bold text-slate-800 truncate">{{ auth()->user()->name }}</p>
            <span class="inline-block mt-0.5 px-2 py-0.5 bg-slate-200/80 text-slate-600 text-[10px] font-semibold rounded-full">
                {{ auth()->user()->getRoleNames()->first() ?? 'Sem papel' }}
            </span>
        </div>
    </div>

    <nav class="p-3 space-y-6 flex-1 text-sm">
        <div>
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-emerald-50 text-emerald-800 font-semibold transition">
                <i class="fa-solid fa-house text-emerald-700"></i>
                <span>Início</span>
            </a>
        </div>
    </nav>
</aside>
