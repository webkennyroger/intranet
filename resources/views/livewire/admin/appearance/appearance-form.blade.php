{{--
    Tela de Aparência (admin).
    Permite trocar as cores do painel (primária, cabeçalho, menu lateral e
    canto da marca), útil para campanhas sazonais. Ao salvar, a página
    recarrega para aplicar as novas cores.
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
    <form wire:submit="save" class="ui-card p-6 space-y-6">
        <div class="grid sm:grid-cols-2 gap-6">
            @php $paletteWithWhite = [...$palette, 'Branco' => '#FFFFFF']; @endphp
            <x-admin.color-picker field="primaryColor" label="Cor de destaque (Primary)" :value="$primaryColor" :palette="$palette" default="#0C4A34" />
            <x-admin.color-picker field="navHeaderColor" label="Canto da marca (Navigation Header)" :value="$navHeaderColor" :palette="$paletteWithWhite" default="#FFFFFF" />
            <x-admin.color-picker field="headerColor" label="Barra superior (Header)" :value="$headerColor" :palette="$paletteWithWhite" default="#FFFFFF" />
            <x-admin.color-picker field="sidebarColor" label="Menu lateral (Sidebar)" :value="$sidebarColor" :palette="$paletteWithWhite" default="#FFFFFF" />
        </div>

        <x-ui.button type="submit">Salvar cores</x-ui.button>
    </form>
</div>
