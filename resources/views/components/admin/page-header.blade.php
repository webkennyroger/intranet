{{--
    Cabeçalho padrão de toda página do admin: ícone, título, subtítulo
    opcional e um slot "actions" para botões à direita. Também suporta
    breadcrumbs opcionais via x-ui.breadcrumbs quando informados. O ícone da
    própria página aparece de novo, enorme e bem apagado, como marca-d'água
    decorativa — acompanha automaticamente qualquer página que já passa um
    "icon", sem precisar de prop nova (mesmo tratamento do cabeçalho do
    site, ver x-site.page-header).
--}}
@props([
    'breadcrumbs' => [],
    'title',
    'subtitle' => null,
    'icon' => null,
])

<div class="relative overflow-hidden rounded-lg p-3" style="background-color: var(--bg-card); border: 1px solid var(--border-color); border-top: 3px solid var(--admin-primary);">
    @if ($icon)
        <i class="fa-solid fa-{{ $icon }} absolute -right-3 -top-5 text-7xl pointer-events-none select-none" style="color: var(--admin-primary); opacity: 0.06;" aria-hidden="true"></i>
    @endif

    <div class="relative z-10">
        @if (count($breadcrumbs))
            <x-ui.breadcrumbs :items="$breadcrumbs" class="mb-3" />
        @endif

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                @if ($icon)
                    <span class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" style="background: linear-gradient(135deg, var(--admin-primary), color-mix(in srgb, var(--admin-primary) 70%, black)); box-shadow: 0 6px 12px -3px color-mix(in srgb, var(--admin-primary) 50%, transparent);">
                        <i class="fa-solid fa-{{ $icon }} text-white"></i>
                    </span>
                @endif
                <div>
                    <h1 class="text-2xl font-bold" style="color: var(--text-primary);">{{ $title }}</h1>
                    @if ($subtitle)
                        <p class="mt-1 text-sm" style="color: var(--text-secondary);">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>

            @isset($actions)
                <div class="flex items-center gap-2 shrink-0">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    </div>
</div>
