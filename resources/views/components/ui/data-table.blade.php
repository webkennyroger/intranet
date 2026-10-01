@props([
    'striped' => true,
    'hoverable' => true,
    'compact' => false,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl overflow-hidden']) }} style="background-color: var(--bg-card); border: 1px solid var(--border-color);">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            @if(isset($header))
                <thead>
                    <tr class="font-medium" style="border-bottom: 1px solid var(--border-color); color: var(--text-secondary);">
                        {{ $header }}
                    </tr>
                </thead>
            @endif

            <tbody style="color: var(--text-primary);">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if(isset($footer))
        <div class="px-6 py-4" style="border-top: 1px solid var(--border-color);">
            {{ $footer }}
        </div>
    @endif
</div>
