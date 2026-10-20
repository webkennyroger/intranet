{{--
    Tela de Aparência (admin).
    Permite trocar as cores do painel (primária, cabeçalho, menu lateral,
    canto da marca e cor do texto), útil para campanhas sazonais. Ao salvar,
    a página recarrega para aplicar as novas cores.
--}}
<div class="space-y-4" x-on:appearance-saved-reload.window="window.location.reload()">
    <!-- Cabeçalho da Página -->
    <x-admin.page-header
        icon="palette"
        title="Aparência"
        subtitle="Define as cores do painel administrativo. Útil para campanhas sazonais (ex.: Outubro Rosa, Novembro Azul)."
        :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => 'Aparência']]"
    />

    <!-- Formulário de Cores -->
    <form wire:submit="save" class="space-y-6">
        @php
            // Branco não entra como opção de destaque: botões, links e
            // realces em cima de cartões/fundos brancos ficariam
            // invisíveis — faz sentido só nas outras linhas (fundo/texto).
            $accentPalette = \Illuminate\Support\Arr::where($palette, fn ($color) => strtoupper($color) !== '#FFFFFF');
        @endphp
        <x-ui.card :padding="false">
            <div>
                <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center gap-4">
                    <div class="flex items-start gap-3 sm:w-72 shrink-0">
                        <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background-color: var(--bg-elevated); color: var(--text-secondary);">
                            <i class="fa-solid fa-droplet"></i>
                        </span>
                        <div>
                            <p class="font-semibold text-sm" style="color: var(--text-primary);">Cor de destaque</p>
                            <p class="text-xs mt-0.5" style="color: var(--text-muted);">Botões, links e realces em todo o painel.</p>
                        </div>
                    </div>
                    <x-admin.color-picker field="primaryColor" :value="$primaryColor" :palette="$accentPalette" default="#0C4A34" />
                </div>

                <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center gap-4" style="border-top: 1px solid var(--border-color);">
                    <div class="flex items-start gap-3 sm:w-72 shrink-0">
                        <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background-color: var(--bg-elevated); color: var(--text-secondary);">
                            <i class="fa-solid fa-user-shield"></i>
                        </span>
                        <div>
                            <p class="font-semibold text-sm" style="color: var(--text-primary);">Canto da marca</p>
                            <p class="text-xs mt-0.5" style="color: var(--text-muted);">Fundo do emblema no topo do menu lateral.</p>
                        </div>
                    </div>
                    <x-admin.color-picker field="navHeaderColor" :value="$navHeaderColor" :palette="$palette" default="#FFFFFF" />
                </div>

                <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center gap-4" style="border-top: 1px solid var(--border-color);">
                    <div class="flex items-start gap-3 sm:w-72 shrink-0">
                        <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background-color: var(--bg-elevated); color: var(--text-secondary);">
                            <i class="fa-solid fa-bars"></i>
                        </span>
                        <div>
                            <p class="font-semibold text-sm" style="color: var(--text-primary);">Barra superior</p>
                            <p class="text-xs mt-0.5" style="color: var(--text-muted);">Fundo da barra fixa no topo de cada página.</p>
                        </div>
                    </div>
                    <x-admin.color-picker field="headerColor" :value="$headerColor" :palette="$palette" default="#FFFFFF" />
                </div>

                <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center gap-4" style="border-top: 1px solid var(--border-color);">
                    <div class="flex items-start gap-3 sm:w-72 shrink-0">
                        <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background-color: var(--bg-elevated); color: var(--text-secondary);">
                            <i class="fa-solid fa-table-columns"></i>
                        </span>
                        <div>
                            <p class="font-semibold text-sm" style="color: var(--text-primary);">Menu lateral</p>
                            <p class="text-xs mt-0.5" style="color: var(--text-muted);">Fundo da coluna de navegação à esquerda.</p>
                        </div>
                    </div>
                    <x-admin.color-picker field="sidebarColor" :value="$sidebarColor" :palette="$palette" default="#FFFFFF" />
                </div>

                <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center gap-4" style="border-top: 1px solid var(--border-color);">
                    <div class="flex items-start gap-3 sm:w-72 shrink-0">
                        <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background-color: var(--bg-elevated); color: var(--text-secondary);">
                            <i class="fa-solid fa-font"></i>
                        </span>
                        <div>
                            <p class="font-semibold text-sm" style="color: var(--text-primary);">Cor do texto</p>
                            <p class="text-xs mt-0.5" style="color: var(--text-muted);">Cor principal dos textos do painel (modo claro — no modo escuro o texto continua claro).</p>
                        </div>
                    </div>
                    <x-admin.color-picker field="textColor" :value="$textColor" :palette="$palette" default="#0F172A" />
                </div>
            </div>
        </x-ui.card>

        <x-ui.button type="submit">Salvar cores</x-ui.button>
    </form>
</div>
