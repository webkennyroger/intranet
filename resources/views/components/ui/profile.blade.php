@props([
    'name' => null,
    'role' => null,
    'avatar' => null,
    'size' => 'md',
])

@php
    // Se <x-ui.avatar> ainda não existir no projeto, usamos aqui um fallback
    // simples de iniciais (mesmo padrão já usado no rodapé da sidebar).
    $initials = $name ? mb_strtoupper(mb_substr(trim($name), 0, 2)) : '?';

    $boxSizes = [
        'sm' => 'w-8 h-8 text-xs',
        'md' => 'w-10 h-10 text-sm',
        'lg' => 'w-12 h-12 text-base',
    ];
    $boxClass = $boxSizes[$size] ?? $boxSizes['md'];
@endphp

<div
    {{ $attributes->merge(['class' => 'flex items-center gap-3 p-3 rounded-xl']) }}
    style="background-color: var(--bg-card); border: 1px solid var(--border-color);"
>
    <div class="flex-shrink-0">
        @if($avatar)
            <img src="{{ $avatar }}" alt="{{ $name }}" class="{{ $boxClass }} rounded-lg object-cover" />
        @else
            <div class="{{ $boxClass }} rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">
                {{ $initials }}
            </div>
        @endif
    </div>

    <div class="flex flex-col min-w-0 flex-1">
        @if($name)
            <span class="font-semibold text-sm truncate" style="color: var(--text-primary);">{{ $name }}</span>
        @endif
        @if($role)
            <span class="text-xs truncate" style="color: var(--text-muted);">{{ $role }}</span>
        @endif
    </div>

    @if($slot->isNotEmpty())
        <div class="flex items-center gap-1 flex-shrink-0">
            {{ $slot }}
        </div>
    @endif
</div>
