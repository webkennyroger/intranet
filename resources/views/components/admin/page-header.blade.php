{{--
    Cabeçalho padrão de toda página do admin: ícone, título, subtítulo
    opcional e um slot "actions" para botões à direita. Também suporta
    breadcrumbs opcionais via x-ui.breadcrumbs quando informados.
--}}
@props([
    'breadcrumbs' => [],
    'title',
    'subtitle' => null,
    'icon' => null,
])

<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
    <div class="flex items-start gap-3">
        @if ($icon)
            <span class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" style="background-color: var(--admin-primary);">
                <i class="fa-solid fa-{{ $icon }} text-white"></i>
            </span>
        @endif
        <div>
            @if (count($breadcrumbs))
                <x-ui.breadcrumbs :items="$breadcrumbs" class="mb-2" />
            @endif
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
