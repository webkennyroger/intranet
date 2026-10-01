@props([
    'tabs' => [],
    'active' => null,
])

<div x-data="{ activeTab: '{{ $active ?? ($tabs[0]['name'] ?? '') }}' }" class="w-full">
    <!-- Tab Headers -->
    <div class="mb-6" style="border-bottom: 1px solid var(--border-color);">
        <nav class="flex space-x-1 -mb-px" aria-label="Tabs">
            @foreach($tabs as $tab)
                <button
                    @click="activeTab = '{{ $tab['name'] }}'"
                    :class="activeTab === '{{ $tab['name'] }}' ? 'border-emerald-500 text-emerald-400' : 'border-transparent'"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors duration-200"
                    style="color: var(--text-secondary);"
                >
                    @if(isset($tab['icon']))
                        <x-ui.icon :name="$tab['icon']" class="w-4 h-4 inline -mt-0.5 mr-1.5" />
                    @endif
                    {{ $tab['label'] ?? $tab['name'] }}
                    @if(isset($tab['count']))
                        <span class="ml-2 px-2 py-0.5 rounded-full text-xs" style="background-color: var(--bg-elevated); color: var(--text-muted);">
                            {{ $tab['count'] }}
                        </span>
                    @endif
                </button>
            @endforeach
        </nav>
    </div>

    <!-- Tab Panels -->
    <div>
        @foreach($tabs as $tab)
            <div
                x-show="activeTab === '{{ $tab['name'] }}'"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
            >
                @if(isset(${$tab['name']}))
                    {{ ${$tab['name']} }}
                @endif
            </div>
        @endforeach
    </div>
</div>
