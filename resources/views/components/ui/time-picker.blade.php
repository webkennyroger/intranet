{{--
    Decisão de design: em vez de construir um dropdown de hora/minuto em
    Alpine (mais código, mais superfície de bug, sem ganho real de UX),
    usamos <input type="time"> nativo estilizado — o próprio navegador já
    entrega seletor de hora acessível, com suporte a teclado e mobile, e
    funciona out-of-the-box com wire:model (dispara "input"/"change" que o
    Livewire já escuta). Formato do valor: "HH:MM" (mesmo formato do
    atributo nativo).
--}}
@props([
    'label' => null,
    'error' => null,
    'hint' => null,
])

<div class="space-y-1.5">
    @if($label)
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <x-ui.icon name="clock" class="w-4 h-4" style="color: var(--text-muted);" />
        </div>

        <input
            type="time"
            {{ $attributes->merge([
                // [color-scheme:light] + dark:[color-scheme:dark]: o indicador
                // nativo do <input type="time"> (ícone/setas do navegador) precisa
                // saber se o fundo é claro ou escuro pra não ficar ilegível; este
                // projeto alterna tema via classe `.light` no <html>, não por
                // prefers-color-scheme, então usamos o mesmo `dark:` customizado
                // (definido em app.css como "não .light") em vez de var(--...).
                'class' => 'w-full rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 transition-all duration-200 pl-10 pr-4 py-2.5 text-sm [color-scheme:light] dark:[color-scheme:dark] ' .
                    ($error ? 'border-red-500/50 focus:ring-red-500/40' : '')
            ]) }}
            style="background-color: var(--bg-input); border: 1px solid {{ $error ? 'rgba(239, 68, 68, 0.5)' : 'var(--border-color)' }}; color: var(--text-primary);"
        />
    </div>

    @if($error)
        <p class="text-xs text-red-400 mt-1.5">{{ $error }}</p>
    @elseif($hint)
        <p class="text-xs mt-1.5" style="color: var(--text-muted);">{{ $hint }}</p>
    @endif
</div>
