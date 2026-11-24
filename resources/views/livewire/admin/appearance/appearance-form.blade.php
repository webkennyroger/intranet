{{--
    Tela de Aparência (admin).
    Escolhe a cor de destaque do sistema (botões, links, item ativo do menu).
    O seletor e a pré-visualização rodam só no navegador (Alpine, estado
    ligado a $primaryColor via entangle): nada é gravado até clicar em
    "Salvar cor", que recarrega a página pra aplicar a nova cor no layout.
--}}
<div
    class="space-y-4"
    x-on:appearance-saved-reload.window="window.location.reload()"
    x-data="{
        color: $wire.entangle('primaryColor'),
        fallback: '#00C950',
        names: @js(array_flip($palette)),
        get valid() { return /^#[0-9a-f]{6}$/i.test(this.color ?? ''); },
        get shown() { return this.valid ? this.color.toUpperCase() : this.fallback; },
        get name() { return this.names[this.shown] ?? null; },
        channels(hex) { return [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16)); },
        get dark() { return '#' + this.channels(this.shown).map((c) => Math.floor(c * 0.8).toString(16).padStart(2, '0')).join(''); },
        get tint() { return this.shown + '1a'; },
        get onAccent() {
            const [r, g, b] = this.channels(this.shown);
            return (r * 299 + g * 587 + b * 114) / 1000 > 150 ? '#312a2a' : '#f4f4f5';
        },
        pick(hex) { this.color = hex; },
    }"
    :style="`--preview: ${shown}; --preview-dark: ${dark}; --preview-tint: ${tint}; --preview-on: ${onAccent};`"
>
    <!-- Cabeçalho da Página -->
    <x-admin.page-header
        icon="palette"
        title="Aparência"
        subtitle="Escolha a cor de destaque do painel. Útil para campanhas sazonais (ex.: Outubro Rosa, Novembro Azul)."
        :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => 'Aparência']]"
    />

    <form wire:submit="save" class="grid grid-cols-1 lg:grid-cols-5 gap-4 items-start">
        <!-- Seletor -->
        <x-ui.card :padding="false" class="lg:col-span-3 transition-colors motion-reduce:transition-none" style="border-top: 3px solid var(--preview);">
            <div class="p-5 sm:p-6 space-y-6">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-colors motion-reduce:transition-none" style="background-color: var(--preview-tint); color: var(--preview-dark);">
                        <i class="fa-solid fa-droplet"></i>
                    </span>
                    <div>
                        <p class="font-bold" style="color: var(--text-primary);">Cor de destaque</p>
                        <p class="text-xs" style="color: var(--text-muted);">Aplicada em botões, links e no item ativo do menu.</p>
                    </div>
                </div>

                <!-- Cor atual: bloco grande + seletor nativo + hex -->
                <div class="flex flex-col sm:flex-row gap-4 sm:items-stretch">
                    <label class="relative block w-full sm:w-44 h-28 rounded-2xl cursor-pointer overflow-hidden focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-(--focus-ring) transition-colors motion-reduce:transition-none" style="background-color: var(--preview); box-shadow: 0 6px 16px -6px var(--preview);">
                        <input type="color" :value="shown" x-on:input="pick($event.target.value.toUpperCase())" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" aria-label="Abrir seletor de cor" />
                        <span class="absolute left-3 bottom-3 inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold" style="background-color: rgb(0 0 0 / 0.18); color: var(--preview-on);">
                            <i class="fa-solid fa-eye-dropper text-[10px]"></i> Escolher outra
                        </span>
                    </label>

                    <div class="flex-1 min-w-0 flex flex-col justify-center gap-2">
                        <label for="primary-hex" class="text-sm font-medium" style="color: var(--text-secondary);">Código da cor</label>
                        <input
                            id="primary-hex"
                            type="text"
                            x-model="color"
                            maxlength="7"
                            spellcheck="false"
                            autocomplete="off"
                            placeholder="#00C950"
                            class="w-full rounded-xl px-4 py-2.5 text-sm font-mono uppercase tabular-nums focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40 focus:border-(--focus-ring) transition"
                            style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);"
                            :aria-invalid="! valid"
                        />
                        <p class="text-xs min-h-4" style="color: var(--text-muted);" x-text="valid ? (name ? name + ' — da paleta Tailwind' : 'Cor personalizada') : 'Use o formato #RRGGBB, por exemplo #00C950.'"></p>
                        @error('primaryColor') <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Paleta -->
                <div>
                    <p class="text-sm font-medium mb-3" style="color: var(--text-secondary);">Paleta rápida</p>
                    <div class="grid grid-cols-6 sm:grid-cols-9 gap-2.5">
                        @foreach ($palette as $swatchName => $swatchColor)
                            <button
                                type="button"
                                x-on:click="pick('{{ $swatchColor }}')"
                                class="relative aspect-square rounded-xl transition-transform duration-150 hover:scale-110 motion-reduce:transition-none motion-reduce:hover:scale-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-(--focus-ring)"
                                style="background-color: {{ $swatchColor }};"
                                title="{{ $swatchName }} — {{ $swatchColor }}"
                                aria-label="{{ $swatchName }} ({{ $swatchColor }})"
                                :aria-pressed="shown === '{{ strtoupper($swatchColor) }}'"
                            >
                                <span x-show="shown === '{{ strtoupper($swatchColor) }}'" x-cloak class="absolute inset-0 flex items-center justify-center text-sm" style="color: {{ \App\Models\SiteSetting::readableTextFor($swatchColor) }};">
                                    <i class="fa-solid fa-check"></i>
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="px-5 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-3" style="border-top: 1px solid var(--border-color);">
                <x-ui.button type="button" variant="ghost" x-on:click="pick(fallback)">
                    <i class="fa-solid fa-rotate-left mr-1.5"></i> Restaurar padrão
                </x-ui.button>
                <x-ui.button type="submit" x-bind:disabled="! valid">
                    <i class="fa-solid fa-floppy-disk mr-1.5"></i> Salvar cor
                </x-ui.button>
            </div>
        </x-ui.card>

        <!-- Pré-visualização: sempre no modo claro, com a cor escolhida ao vivo -->
        <x-ui.card :padding="false" class="lg:col-span-2 overflow-hidden">
            <div class="p-5 sm:p-6 space-y-5">
                <div>
                    <p class="font-bold" style="color: var(--text-primary);">Pré-visualização</p>
                    <p class="text-xs" style="color: var(--text-muted);">Atualiza na hora, antes de salvar. Mostra o modo claro.</p>
                </div>

                <div class="rounded-2xl p-4 space-y-4 bg-white" style="border: 1px solid #e2e8f0; color: #0f172a;">
                    <!-- Item de menu -->
                    <div class="space-y-1">
                        <div class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-semibold transition-colors motion-reduce:transition-none" style="background-color: var(--preview-tint); color: var(--preview-dark);">
                            <i class="fa-solid fa-gauge-high w-4 text-center"></i> Painel
                        </div>
                        <div class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-slate-600">
                            <i class="fa-solid fa-newspaper w-4 text-center"></i> Mural
                        </div>
                    </div>

                    <!-- Botões -->
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold shadow-lg shadow-black/10 transition-colors motion-reduce:transition-none" style="background-color: var(--preview); color: var(--preview-on);">
                            <i class="fa-solid fa-plus text-xs"></i> Novo artigo
                        </span>
                        <span class="text-sm font-semibold underline underline-offset-4 transition-colors motion-reduce:transition-none" style="color: var(--preview-dark);">Ver todos</span>
                    </div>

                    <!-- Selo + campo em foco -->
                    <div class="space-y-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors motion-reduce:transition-none" style="background-color: var(--preview-tint); color: var(--preview-dark);">Publicado</span>
                        <div class="rounded-xl px-3 py-2 text-sm text-slate-500 bg-white transition-shadow motion-reduce:transition-none" style="border: 1px solid var(--preview); box-shadow: 0 0 0 3px var(--preview-tint);">
                            Campo em foco
                        </div>
                    </div>
                </div>

                <p class="text-xs" style="color: var(--text-muted);">No modo escuro, o sistema ajusta os tons desta cor automaticamente para manter o contraste.</p>
            </div>
        </x-ui.card>
    </form>
</div>
