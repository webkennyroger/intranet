{{--
    Layout-base de toda página do painel admin: head com meta tags, sidebar
    fixa, header do topo, área de conteúdo (slot) e footer.
    Monta um conjunto de variáveis CSS (--admin-primary, --admin-sidebar,
    --admin-header, etc.) a partir das cores salvas em Aparência
    (SiteSetting), tanto para o modo claro quanto para o bloco ".dark" do
    modo escuro, incluindo o cálculo de contraste do item ativo do menu
    conforme a sidebar é clara ou escura.
--}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Administração' }} — Intranet DPEMT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @php
        $appearance = \App\Models\SiteSetting::current();
        $navHeaderText = \App\Models\SiteSetting::readableTextFor($appearance->nav_header_color);
        $headerText = \App\Models\SiteSetting::readableTextFor($appearance->header_color);
        $sidebarText = \App\Models\SiteSetting::readableTextFor($appearance->sidebar_color);
        $darkSurface = \App\Models\SiteSetting::darkBgFor($appearance->primary_color);
        $darkBody = \App\Models\SiteSetting::darkBodyBgFor($appearance->primary_color);
        $darkElevated = \App\Models\SiteSetting::darkElevatedBgFor($appearance->primary_color);
        $darkHover = \App\Models\SiteSetting::darkHoverBgFor($appearance->primary_color);
        // O item ativo do menu precisa de contraste com o fundo real da
        // sidebar — não dá para usar sempre "fundo clarinho + texto escuro"
        // (bom quando a sidebar é branca), senão some quando a sidebar tem
        // a mesma cor escura da Primary. Troca para o par "fundo escuro +
        // texto claro" (mesma lógica do dark mode) quando a sidebar é escura.
        $sidebarIsLight = $sidebarText === '#312a2a';
        $sidebarActiveBg = $sidebarIsLight ? $appearance->primaryLightColor() : \App\Models\SiteSetting::darkActiveHighlightBgFor($appearance->primary_color);
        $sidebarActiveText = $sidebarIsLight ? $appearance->primaryHoverColor() : \App\Models\SiteSetting::darkTextFor($appearance->primary_color);
    @endphp
    <!-- Variáveis CSS de Aparência -->
    <style>
        :root {
            --admin-primary: {{ $appearance->primary_color }};
            --admin-primary-dark: {{ $appearance->primaryHoverColor() }};
            --admin-primary-light: {{ $appearance->primaryLightColor() }};
            --admin-nav-header: {{ $appearance->nav_header_color }};
            --admin-nav-header-text: {{ $navHeaderText }};
            --admin-header: {{ $appearance->header_color }};
            --admin-header-text: {{ $headerText }};
            --admin-sidebar: {{ $appearance->sidebar_color }};
            --admin-sidebar-text: {{ $sidebarText }};
            --admin-sidebar-active-bg: {{ $sidebarActiveBg }};
            --admin-sidebar-active-text: {{ $sidebarActiveText }};
            --focus-ring: var(--admin-primary);
        }
        /* No escuro, cada área (nav-header/header/sidebar) usa uma versão
           escurecida no mesmo matiz da cor escolhida — mesma lógica da
           Aparência, só que agora aplicada via Tailwind puro + estas
           variáveis, sem nenhum CSS de terceiro. Essas variáveis não têm
           mais valor fixo no app.css — sem isso, a declaração estática
           carregada depois deste bloco (pelo Vite, mais abaixo) sempre
           ganhava do cascade e o modo escuro nunca acompanhava a cor
           escolhida aqui. */
        /* "html.dark" (não só ".dark") de propósito: --bg-base/-surface/
           -card/-border-color (e as novas -elevated/-hover/-input/-subtle)
           também têm valor padrão no ":root" do app.css — ":root" e
           ".dark" têm a MESMA especificidade, então sem o "html" extra
           aqui essa regra perderia pro ":root" do app.css (carregado
           depois) sempre que ambos baterem no mesmo elemento <html>. */
        html.dark {
            --admin-primary-dark: {{ $appearance->darkActiveTextColor() }};
            --admin-primary-light: {{ $appearance->darkActiveBgColor() }};
            --admin-nav-header: {{ \App\Models\SiteSetting::darkBgFor($appearance->nav_header_color) }};
            --admin-nav-header-text: #f4f4f5;
            --admin-header: {{ \App\Models\SiteSetting::darkBgFor($appearance->header_color) }};
            --admin-header-text: #f4f4f5;
            --admin-sidebar: {{ \App\Models\SiteSetting::darkBgFor($appearance->sidebar_color) }};
            --admin-sidebar-text: #f4f4f5;
            --admin-sidebar-active-bg: {{ \App\Models\SiteSetting::darkActiveHighlightBgFor($appearance->primary_color) }};
            --admin-sidebar-active-text: {{ $appearance->darkActiveTextColor() }};
            --bg-base: {{ $darkBody }};
            --bg-surface: {{ $darkSurface }};
            --bg-elevated: {{ $darkElevated }};
            --bg-hover: {{ $darkHover }};
            --bg-input: {{ $darkHover }}80;
            --bg-card: {{ $darkSurface }};
            --border-color: {{ $darkElevated }};
            --border-subtle: {{ $darkElevated }}80;
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body
    class="site-dark-bg font-sans antialiased bg-slate-100 dark:text-(--text-primary) text-slate-800"
    x-data="{ sidebarCollapsed: localStorage.getItem('adminSidebarCollapsed') === 'true', mobileSidebarOpen: false }"
    x-init="$watch('sidebarCollapsed', (value) => localStorage.setItem('adminSidebarCollapsed', value))"
>
    <!-- Overlay Mobile (Sidebar) -->
    <div
        x-show="mobileSidebarOpen"
        x-cloak
        @click="mobileSidebarOpen = false"
        class="fixed inset-0 bg-black/40 z-40 lg:hidden"
    ></div>

    <!-- Sidebar -->
    <x-layout.admin-sidebar />

    <div class="min-h-screen flex flex-col lg:ml-64 transition-all duration-300" :class="sidebarCollapsed ? 'lg:ml-26!' : ''">
        <!-- Header -->
        <x-layout.admin-header :title="$title ?? null" />

        <!-- Conteúdo Principal (Slot) -->
        <main class="site-dark-bg flex-1 p-3 bg-slate-100">
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="text-center text-xs py-4 border-t" style="color: var(--text-muted); border-color: var(--border-color);">
            Copyright &copy; {{ now()->year }} Intranet DPEMT. Todos os direitos reservados.
        </footer>
    </div>

    <!-- Modal de Confirmação (substitui o confirm() nativo do navegador em toda ação destrutiva) -->
    <x-ui.confirm-modal />

    <!-- Notificações (toast) de toda ação de criar/editar/excluir/atualizar -->
    <x-ui.toast />

    @livewireScripts
</body>
</html>
