@props([
    'label' => null,
    'value' => null,
])

@php
    // Se vier com wire:model, o estado "selected" fica entrelaçado com o
    // Livewire (uso standalone). Se não vier (uso aninhado dentro de
    // <x-ui.date-picker>), o componente pai controla "selected" via
    // x-model, graças ao x-modelable abaixo — o calendário em si continua
    // "burro" e não precisa saber se está sendo usado sozinho ou não.
    $modelName = $attributes->wire('model')->value();
@endphp

<div
    x-data="{
        selected: @if($modelName) $wire.entangle('{{ $modelName }}') @else @js($value) @endif,
        viewYear: null,
        viewMonth: null,
        init() {
            const base = this.selected ? new Date(this.selected + 'T00:00:00') : new Date();
            this.viewYear = base.getFullYear();
            this.viewMonth = base.getMonth();
        },
        get monthLabel() {
            return new Date(this.viewYear, this.viewMonth, 1).toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
        },
        get daysInMonth() {
            return new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
        },
        get firstDayOffset() {
            return new Date(this.viewYear, this.viewMonth, 1).getDay();
        },
        get weeks() {
            const days = [];
            for (let i = 0; i < this.firstDayOffset; i++) days.push(null);
            for (let d = 1; d <= this.daysInMonth; d++) days.push(d);
            while (days.length % 7 !== 0) days.push(null);
            const chunks = [];
            for (let i = 0; i < days.length; i += 7) chunks.push(days.slice(i, i + 7));
            return chunks;
        },
        prevMonth() {
            this.viewMonth--;
            if (this.viewMonth < 0) { this.viewMonth = 11; this.viewYear--; }
        },
        nextMonth() {
            this.viewMonth++;
            if (this.viewMonth > 11) { this.viewMonth = 0; this.viewYear++; }
        },
        dateString(day) {
            const mm = String(this.viewMonth + 1).padStart(2, '0');
            const dd = String(day).padStart(2, '0');
            return `${this.viewYear}-${mm}-${dd}`;
        },
        isToday(day) {
            if (! day) return false;
            const t = new Date();
            return t.getFullYear() === this.viewYear && t.getMonth() === this.viewMonth && t.getDate() === day;
        },
        isSelected(day) {
            return day && this.selected === this.dateString(day);
        },
        select(day) {
            if (! day) return;
            this.selected = this.dateString(day);
        },
    }"
    x-modelable="selected"
    x-init="init()"
    {{ $attributes->whereDoesntStartWith('wire:model')->merge(['class' => 'space-y-1.5']) }}
>
    @if($label)
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">
            {{ $label }}
        </label>
    @endif

    <div class="rounded-xl p-3" style="background-color: var(--bg-surface); border: 1px solid var(--border-color);">
        <div class="flex items-center justify-between mb-3">
            <button
                type="button"
                @click="prevMonth()"
                class="p-1.5 rounded-lg transition-colors duration-150 hover:bg-[var(--bg-hover)]"
                style="color: var(--text-secondary);"
                aria-label="Mês anterior"
            >
                <x-ui.icon name="chevron-left" class="w-4 h-4" />
            </button>

            <span class="text-sm font-semibold capitalize" style="color: var(--text-primary);" x-text="monthLabel"></span>

            <button
                type="button"
                @click="nextMonth()"
                class="p-1.5 rounded-lg transition-colors duration-150 hover:bg-[var(--bg-hover)]"
                style="color: var(--text-secondary);"
                aria-label="Próximo mês"
            >
                <x-ui.icon name="chevron-right" class="w-4 h-4" />
            </button>
        </div>

        <div class="grid grid-cols-7 gap-1 mb-1 text-center text-xs font-medium" style="color: var(--text-muted);">
            <span>Dom</span>
            <span>Seg</span>
            <span>Ter</span>
            <span>Qua</span>
            <span>Qui</span>
            <span>Sex</span>
            <span>Sáb</span>
        </div>

        <template x-for="(week, wIndex) in weeks" :key="wIndex">
            <div class="grid grid-cols-7 gap-1 mb-1">
                <template x-for="(day, dIndex) in week" :key="dIndex">
                    <div class="aspect-square">
                        <button
                            type="button"
                            x-show="day !== null"
                            @click="select(day)"
                            x-text="day"
                            class="w-full h-full rounded-lg text-sm flex items-center justify-center transition-colors duration-150"
                            :class="isSelected(day) ? 'bg-emerald-500 text-white font-semibold' : (isToday(day) ? 'font-semibold' : '')"
                            :style="isSelected(day) ? '' : (isToday(day) ? 'border: 1px solid var(--border-color); color: var(--text-primary);' : 'color: var(--text-secondary);')"
                        ></button>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>
