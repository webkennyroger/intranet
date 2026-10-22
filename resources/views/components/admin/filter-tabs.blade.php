{{--
    Abas de filtro usadas no Histórico de Atividades. Diferente de
    x-ui.tabs (que troca o conteúdo no cliente via Alpine), aqui cada aba
    dispara uma ação Livewire via wire:click (padrão "action"), ou seja,
    o filtro é resolvido no servidor. Aceita um contador opcional por aba.
--}}
@props([
    'tabs' => [],
    'active' => null,
    'action' => 'setFilter',
    'colors' => [],
    'icons' => [],
])

<div class="flex flex-wrap items-center gap-9" style="border-bottom: 1px solid var(--border-color);">
    @foreach ($tabs as $value => $tab)
        @php
            $label = is_array($tab) ? $tab['label'] : $tab;
            $count = is_array($tab) ? ($tab['count'] ?? null) : null;
            $isActive = $active === $value;
            $color = $colors[$value] ?? 'var(--admin-primary)';
            $icon = $icons[$value] ?? null;
        @endphp
        <button
            type="button"
            wire:click="{{ $action }}('{{ $value }}')"
            class="group relative flex items-center gap-2 pb-3 text-[13px] font-medium transition"
            style="color: {{ $isActive ? $color : 'var(--text-primary)' }};"
        >
            @if ($icon)
                <span class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 transition" style="background-color: {{ $isActive ? $color : 'color-mix(in srgb, var(--text-muted) 16%, transparent)' }};">
                    <i class="fa-solid fa-{{ $icon }} text-[9px] transition" style="color: {{ $isActive ? 'white' : 'var(--text-muted)' }};"></i>
                </span>
            @endif

            {{ $label }}

            @if ($count)
                <span class="inline-flex items-center justify-center text-[10px] font-bold px-1.5" style="border-radius: 4px; background-color: color-mix(in srgb, {{ $color }} 16%, transparent); color: {{ $color }};">{{ $count }}</span>
            @endif

            <span class="absolute inset-x-0 bottom-0 h-0.5 origin-left transition-transform duration-200" style="background-color: {{ $color }}; transform: scaleX({{ $isActive ? 1 : 0 }});"></span>
        </button>
    @endforeach
</div>
