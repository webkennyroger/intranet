{{--
    Tela de gestão de Eventos (admin): calendário com visões Ano/Mês/
    Semana/Dia/Lista. Visual com paleta própria (fixa, baseada na
    referência de design do calendário) — exceção intencional ao tema
    var(--...)/modo escuro do resto do admin, por isso não usa nenhum
    var(--admin-...) nem classes dark: dentro do bloco abaixo.
--}}
<div
    class="space-y-4"
    style="
        --cal-primary: var(--admin-primary, #0C4A34); --cal-primary-dark: var(--admin-primary-dark, #093524);
        --cal-success: #0ab39c; --cal-info: #299cdb;
        --cal-warning: #f7b84b; --cal-danger: #f06548; --cal-dark: #212529;
        --cal-bg: #ffffff; --cal-bg-soft: #f3f3f9;
        --cal-border: #e9ebec; --cal-text: #1a1d21; --cal-text-muted: #878a99;
    "
>
    <x-admin.page-header
        icon="calendar-days"
        title="Eventos"
        subtitle="Gerencie os eventos exibidos no site."
        :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => 'Eventos']]"
    />

    <div class="flex flex-col lg:flex-row gap-4">
        {{-- Barra lateral --}}
        <aside class="lg:w-72 shrink-0 rounded-2xl p-4 space-y-5" style="background-color: var(--cal-bg); border: 1px solid var(--cal-border);">
            @can('events.create')
                <button
                    type="button"
                    wire:click="closeForm"
                    x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'event-form' }))"
                    class="w-full rounded-xl px-4 py-2.5 text-sm font-bold text-white transition hover:brightness-110"
                    style="background-color: var(--cal-primary);"
                >
                    <i class="fa-solid fa-plus mr-1.5"></i> Criar novo evento
                </button>

                <p class="text-xs" style="color: var(--cal-text-muted);">Arraste e solte seu evento ou clique no calendário.</p>

                <div class="space-y-2">
                    @foreach ($this->eventCategories as $category)
                        <div
                            draggable="true"
                            x-on:dragstart="$event.dataTransfer.setData('application/json', JSON.stringify({{ Illuminate\Support\Js::from(['type' => 'category', 'label' => $category->name, 'categoryId' => $category->id]) }}))"
                            class="group relative flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold cursor-grab select-none"
                            style="background-color: color-mix(in srgb, {{ $category->color }} 12%, white); color: {{ $category->color }};"
                        >
                            <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $category->color }};"></span>
                            <span class="truncate">{{ $category->name }}</span>

                            <div class="hidden group-hover:flex items-center gap-1 ml-auto shrink-0">
                                @can('events.update')
                                    <button
                                        type="button"
                                        wire:click="editCategory({{ $category->id }})"
                                        x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'event-category-form' }))"
                                        class="w-6 h-6 rounded flex items-center justify-center hover:brightness-95"
                                        style="background-color: #ffffff;"
                                    ><i class="fa-solid fa-pen text-[10px]"></i></button>
                                @endcan
                                @can('events.delete')
                                    <button
                                        type="button"
                                        @click="confirmAction('Excluir a categoria \'{{ $category->name }}\'?', () => $wire.deleteCategory({{ $category->id }}))"
                                        class="w-6 h-6 rounded flex items-center justify-center hover:brightness-95"
                                        style="background-color: #ffffff;"
                                    ><i class="fa-solid fa-trash text-[10px]"></i></button>
                                @endcan
                            </div>
                        </div>
                    @endforeach

                    <button
                        type="button"
                        wire:click="closeCategoryForm"
                        x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'event-category-form' }))"
                        class="w-full flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition hover:brightness-95"
                        style="border: 2px dashed var(--cal-border); color: var(--cal-text-muted);"
                    ><i class="fa-solid fa-plus"></i> Nova categoria</button>
                </div>
            @endcan

            <div>
                <p class="text-sm font-bold" style="color: var(--cal-text);">Próximos eventos</p>
                <p class="text-xs mb-3" style="color: var(--cal-text-muted);">Não perca os eventos programados.</p>

                @if ($this->upcomingEvents->isEmpty())
                    <div class="rounded-xl p-3 text-xs" style="background-color: #e7f1fb; color: #2a5f8a;">
                        <i class="fa-solid fa-circle-info mr-1"></i> Bem-vindo(a) ao seu calendário! Os próximos eventos aparecerão aqui.
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach ($this->upcomingEvents as $event)
                            <button
                                type="button"
                                wire:click="viewEvent({{ $event->id }})"
                                x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'event-view' }))"
                                class="w-full text-left rounded-xl p-3 transition hover:brightness-95"
                                style="background-color: var(--cal-bg-soft);"
                            >
                                <div class="flex items-center gap-1.5 text-[11px] font-semibold" style="color: {{ $event->colorHex() }};">
                                    <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $event->colorHex() }};"></span>
                                    {{ $event->starts_at->translatedFormat('d \d\e F \d\e Y') }}
                                </div>
                                <p class="text-sm font-bold mt-0.5 truncate" style="color: var(--cal-text);">{{ $event->title }}</p>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </aside>

        {{-- Calendário --}}
        <div class="flex-1 min-w-0 rounded-2xl p-4" style="background-color: var(--cal-bg); border: 1px solid var(--cal-border);">
            {{-- Barra de ferramentas — fica FORA do island: precisa re-renderizar
                 a grade a cada navegação/troca de view. --}}
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="previous" class="w-9 h-9 rounded-lg flex items-center justify-center transition hover:brightness-95" style="background-color: var(--cal-bg-soft); color: var(--cal-text);">
                        <i class="fa-solid fa-chevron-left text-xs"></i>
                    </button>
                    <button type="button" wire:click="next" class="w-9 h-9 rounded-lg flex items-center justify-center transition hover:brightness-95" style="background-color: var(--cal-bg-soft); color: var(--cal-text);">
                        <i class="fa-solid fa-chevron-right text-xs"></i>
                    </button>
                    <button type="button" wire:click="goToToday" class="rounded-lg px-3 py-2 text-xs font-bold transition hover:brightness-95" style="background-color: color-mix(in srgb, var(--cal-primary) 12%, white); color: var(--cal-primary);">
                        Hoje
                    </button>
                    <p class="text-base font-bold ml-1" style="color: var(--cal-text);">{{ ucfirst($this->periodLabel) }}</p>
                </div>

                <div class="inline-flex items-center gap-1 rounded-lg p-1" style="background-color: var(--cal-bg-soft);">
                    @foreach (['year' => 'Ano', 'month' => 'Mês', 'week' => 'Semana', 'day' => 'Dia', 'list' => 'Lista'] as $value => $label)
                        <button
                            type="button"
                            wire:click="setView('{{ $value }}')"
                            class="rounded-lg px-3 py-1.5 text-xs font-bold transition"
                            style="{{ $calendarView === $value ? 'background-color: var(--cal-primary); color: #fff;' : 'color: var(--cal-text-muted);' }}"
                        >{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            @island(name: 'calendar-grid', always: true)
                @switch($calendarView)
                    @case('year')
                        @include('livewire.admin.events.partials.year')
                        @break
                    @case('week')
                        @include('livewire.admin.events.partials.week')
                        @break
                    @case('day')
                        @include('livewire.admin.events.partials.day')
                        @break
                    @case('list')
                        @include('livewire.admin.events.partials.list')
                        @break
                    @default
                        @include('livewire.admin.events.partials.month')
                @endswitch
            @endisland
        </div>
    </div>

    {{-- Modal: formulário de criar/editar evento --}}
    <div
        x-data="{ open: false }"
        x-on:open-modal.window="(Array.isArray($event.detail) ? $event.detail[0] : $event.detail) === 'event-form' && (open = true)"
        x-on:close-modal.window="(Array.isArray($event.detail) ? $event.detail[0] : $event.detail) === 'event-form' && (open = false)"
        x-on:keydown.escape.window="open = false"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-modal="true"
    >
        <div x-show="open" x-transition.opacity class="fixed inset-0" style="background-color: rgba(0,0,0,.5);" @click="open = false"></div>

        <div class="relative flex min-h-full items-center justify-center p-4">
            <div x-show="open" x-transition class="w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden" style="background-color: #ffffff;">
                <div class="px-6 py-4 flex items-center justify-between" style="background-color: var(--cal-primary);">
                    <p class="font-bold text-white">{{ $this->editingEvent ? 'Editar evento' : 'Novo evento' }}</p>
                    <button @click="open = false" class="text-white/80 hover:text-white">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="px-6 py-5">
                    <livewire:admin.events.event-form
                        :event="$this->editingEvent"
                        :prefillDate="$this->prefillStartsAt"
                        :key="'event-form-'.($this->editingEventId ?? 'new').'-'.($this->prefillStartsAt ?? '')"
                    />
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: criar/editar categoria --}}
    <div
        x-data="{ open: false }"
        x-on:open-modal.window="(Array.isArray($event.detail) ? $event.detail[0] : $event.detail) === 'event-category-form' && (open = true)"
        x-on:close-modal.window="(Array.isArray($event.detail) ? $event.detail[0] : $event.detail) === 'event-category-form' && (open = false)"
        x-on:keydown.escape.window="open = false"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-modal="true"
    >
        <div x-show="open" x-transition.opacity class="fixed inset-0" style="background-color: rgba(0,0,0,.5);" @click="open = false"></div>

        <div class="relative flex min-h-full items-center justify-center p-4">
            <div x-show="open" x-transition class="w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden" style="background-color: #ffffff;">
                <div class="px-6 py-4 flex items-center justify-between" style="background-color: var(--cal-primary);">
                    <p class="font-bold text-white">{{ $this->editingCategory ? 'Editar categoria' : 'Nova categoria' }}</p>
                    <button @click="open = false" class="text-white/80 hover:text-white">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="px-6 py-5">
                    <livewire:admin.events.event-category-form
                        :category="$this->editingCategory"
                        :key="'event-category-form-'.($this->editingCategoryId ?? 'new')"
                    />
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: visualização de um evento --}}
    <div
        x-data="{ open: false }"
        x-on:open-modal.window="(Array.isArray($event.detail) ? $event.detail[0] : $event.detail) === 'event-view' && (open = true)"
        x-on:close-modal.window="(Array.isArray($event.detail) ? $event.detail[0] : $event.detail) === 'event-view' && (open = false)"
        x-on:keydown.escape.window="open = false"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-modal="true"
    >
        <div x-show="open" x-transition.opacity class="fixed inset-0" style="background-color: rgba(0,0,0,.5);" @click="open = false"></div>

        <div class="relative flex min-h-full items-center justify-center p-4">
            @php $viewed = $this->selectedEvent; @endphp
            <div x-show="open" x-transition class="w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden" style="background-color: #ffffff;">
                @if ($viewed)
                    <div class="px-6 py-4 flex items-center justify-between" style="background-color: {{ $viewed->colorHex() }};">
                        <p class="font-bold text-white truncate">{{ $viewed->title }}</p>
                        <button @click="open = false" class="text-white/80 hover:text-white shrink-0 ml-2">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="px-6 py-5 space-y-3">
                        @if ($viewed->coverImageUrl())
                            <img src="{{ $viewed->coverImageUrl() }}" alt="{{ $viewed->title }}" class="w-full h-auto rounded-xl">
                        @endif

                        <div class="flex items-center gap-2 text-sm" style="color: var(--cal-text);">
                            <i class="fa-regular fa-clock" style="color: var(--cal-text-muted);"></i>
                            {{ $viewed->starts_at->translatedFormat('d \d\e F \d\e Y') }} · {{ $viewed->starts_at->format('H:i') }}@if ($viewed->ends_at) às {{ $viewed->ends_at->format('H:i') }} @endif
                        </div>

                        @if ($viewed->location)
                            <div class="flex items-center gap-2 text-sm" style="color: var(--cal-text);">
                                <i class="fa-solid fa-location-dot" style="color: var(--cal-text-muted);"></i>
                                {{ $viewed->location }}
                            </div>
                        @endif

                        @if ($viewed->description)
                            <p class="text-sm leading-relaxed" style="color: var(--cal-text-muted);">{{ $viewed->description }}</p>
                        @endif

                        @if ($viewed->link || $viewed->file_path)
                            <div class="flex flex-wrap items-center gap-2 pt-2">
                                @if ($viewed->link)
                                    <a href="{{ $viewed->link }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-bold text-white" style="background-color: {{ $viewed->colorHex() }};">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir link
                                    </a>
                                @endif
                                @if ($viewed->file_path)
                                    <a href="{{ $viewed->fileUrl() }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-bold" style="background-color: var(--cal-bg-soft); color: var(--cal-text);">
                                        <i class="fa-solid fa-{{ $viewed->fileIcon() }}"></i> Baixar anexo
                                    </a>
                                @endif
                            </div>
                        @endif

                        <div class="flex items-center gap-2 pt-3 border-t" style="border-color: var(--cal-border);">
                            @can('events.update')
                                <button
                                    type="button"
                                    wire:click="edit({{ $viewed->id }})"
                                    x-on:click="open = false; window.dispatchEvent(new CustomEvent('open-modal', { detail: 'event-form' }))"
                                    class="rounded-lg px-3 py-2 text-xs font-bold transition hover:brightness-95"
                                    style="background-color: color-mix(in srgb, var(--cal-primary) 12%, white); color: var(--cal-primary);"
                                ><i class="fa-solid fa-pen mr-1"></i> Editar</button>
                            @endcan
                            @can('events.delete')
                                <button
                                    type="button"
                                    @click="confirmAction('Confirma a exclusão deste evento?', () => $wire.delete({{ $viewed->id }}))"
                                    class="rounded-lg px-3 py-2 text-xs font-bold transition hover:brightness-95"
                                    style="background-color: color-mix(in srgb, var(--cal-danger) 12%, white); color: var(--cal-danger);"
                                ><i class="fa-solid fa-trash mr-1"></i> Excluir</button>
                            @endcan
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
