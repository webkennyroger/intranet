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
        @php
            // Linhas da tabela: campo, ícone, título, descrição, paleta e cor
            // padrão — monta cada bloco de swatches num loop só, em vez de
            // repetir a mesma estrutura 5 vezes.
            $rows = [
                ['field' => 'primaryColor', 'value' => $primaryColor, 'icon' => 'droplet', 'title' => 'Cor de destaque', 'description' => 'Botões, links e realces em todo o painel.', 'palette' => $accentPalette, 'default' => '#0C4A34'],
                ['field' => 'navHeaderColor', 'value' => $navHeaderColor, 'icon' => 'user-shield', 'title' => 'Canto da marca', 'description' => 'Fundo do emblema no topo do menu lateral.', 'palette' => $palette, 'default' => '#FFFFFF'],
                ['field' => 'headerColor', 'value' => $headerColor, 'icon' => 'bars', 'title' => 'Barra superior', 'description' => 'Fundo da barra fixa no topo de cada página.', 'palette' => $palette, 'default' => '#FFFFFF'],
                ['field' => 'sidebarColor', 'value' => $sidebarColor, 'icon' => 'table-columns', 'title' => 'Menu lateral', 'description' => 'Fundo da coluna de navegação à esquerda.', 'palette' => $palette, 'default' => '#FFFFFF'],
                ['field' => 'textColor', 'value' => $textColor, 'icon' => 'font', 'title' => 'Cor do texto', 'description' => 'Cor principal dos textos do painel (modo claro — no modo escuro o texto continua claro).', 'palette' => $palette, 'default' => '#0F172A'],
            ];
        @endphp

        {{-- Friso no topo na própria cor de destaque escolhida — a única
             página do admin onde isso faz sentido, já que é literalmente o
             que está sendo editado. --}}
        <x-ui.card :padding="false" style="border-top: 3px solid {{ $primaryColor }};">
            <div>
                @foreach ($rows as $i => $row)
                    <div class="group p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center gap-4 transition hover:bg-(--bg-hover)" @if ($i > 0) style="border-top: 1px solid var(--border-color);" @endif>
                        <div class="flex items-start gap-3 sm:w-72 shrink-0">
                            <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition group-hover:scale-105" style="background-color: var(--bg-elevated); color: var(--text-secondary);">
                                <i class="fa-solid fa-{{ $row['icon'] }}"></i>
                            </span>
                            <div>
                                <p class="font-semibold text-sm" style="color: var(--text-primary);">{{ $row['title'] }}</p>
                                <p class="text-xs mt-0.5" style="color: var(--text-muted);">{{ $row['description'] }}</p>
                                <p class="text-[11px] font-mono font-bold uppercase mt-1" style="color: var(--text-secondary);">{{ $row['value'] }}</p>
                            </div>
                        </div>
                        <x-admin.color-picker :field="$row['field']" :value="$row['value']" :palette="$row['palette']" :default="$row['default']" />
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.button type="submit">Salvar cores</x-ui.button>
    </form>
</div>
