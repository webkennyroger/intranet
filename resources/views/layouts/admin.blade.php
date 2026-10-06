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
        }
        /* No escuro, cada área (nav-header/header/sidebar) usa uma versão
           escurecida no mesmo matiz da cor escolhida — mesma lógica da
           Aparência, só que agora aplicada via Tailwind puro + estas
           variáveis, sem nenhum CSS de terceiro. */
        .dark {
            --admin-primary-dark: {{ $appearance->darkActiveTextColor() }};
            --admin-primary-light: {{ $appearance->darkActiveBgColor() }};
            --admin-nav-header: {{ \App\Models\SiteSetting::darkBgFor($appearance->nav_header_color) }};
            --admin-nav-header-text: #e6f2ec;
            --admin-header: {{ \App\Models\SiteSetting::darkBgFor($appearance->header_color) }};
            --admin-header-text: #e6f2ec;
            --admin-sidebar: {{ \App\Models\SiteSetting::darkBgFor($appearance->sidebar_color) }};
            --admin-sidebar-text: #e6f2ec;
            --admin-sidebar-active-bg: {{ \App\Models\SiteSetting::darkActiveHighlightBgFor($appearance->primary_color) }};
            --admin-sidebar-active-text: {{ $appearance->darkActiveTextColor() }};
            --bg-base: {{ $darkBody }};
            --bg-surface: {{ $darkSurface }};
            --bg-card: {{ $darkSurface }}f2;
            --border-color: {{ $darkSurface }};
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="site-dark-bg font-sans antialiased bg-slate-100 dark:text-[#e6f2ec] text-slate-800" x-data="{ sidebarCollapsed: false, mobileSidebarOpen: false }">
    <!-- Overlay Mobile (Sidebar) -->
    <div
        x-show="mobileSidebarOpen"
        x-cloak
        @click="mobileSidebarOpen = false"
        class="fixed inset-0 bg-black/40 z-40 lg:hidden"
    ></div>

    <!-- Sidebar -->
    <x-layout.admin-sidebar />

    <div class="min-h-screen flex flex-col lg:ml-64 transition-all duration-300" :class="sidebarCollapsed ? 'lg:ml-20!' : ''">
        <!-- Header -->
        <x-layout.admin-header :title="$title ?? null" />

        <!-- Conteúdo Principal (Slot) -->
        <main class="site-dark-bg flex-1 p-6 bg-slate-100">
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="text-center text-xs py-4 border-t" style="color: var(--text-muted); border-color: var(--border-color);">
            Copyright &copy; {{ now()->year }} Intranet DPEMT. Todos os direitos reservados.
        </footer>
    </div>

    @livewireScripts
</body>
</html>
