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
])

<div class="flex flex-wrap gap-2">
    @foreach ($tabs as $value => $tab)
        @php
            $label = is_array($tab) ? $tab['label'] : $tab;
            $count = is_array($tab) ? ($tab['count'] ?? null) : null;
            $isActive = $active === $value;
        @endphp
        <button
            type="button"
            wire:click="{{ $action }}('{{ $value }}')"
            class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
            @if ($isActive)
                style="background-color: var(--admin-primary); color: white;"
            @else
                style="color: var(--text-secondary);"
            @endif
            onmouseover="if (!this.hasAttribute('data-active')) this.style.backgroundColor = 'var(--bg-hover)';"
            onmouseout="if (!this.hasAttribute('data-active')) this.style.backgroundColor = '';"
            @if ($isActive) data-active @endif
        >
            {{ $label }}
            @if (! is_null($count))
                <span
                    class="ml-1.5 rounded-full px-2 py-0.5 text-xs"
                    @if ($isActive)
                        style="background-color: rgba(255, 255, 255, 0.25); color: white;"
                    @else
                        style="background-color: var(--bg-elevated); color: var(--text-muted);"
                    @endif
                >{{ $count }}</span>
            @endif
        </button>
    @endforeach
</div>
