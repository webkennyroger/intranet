{{--
    Layout-base de toda página do painel admin: head com meta tags, sidebar
    fixa, header do topo, área de conteúdo (slot) e footer.
    Monta as variáveis CSS derivadas da cor de destaque salva em Aparência
    (SiteSetting::primary_color) — --admin-primary e o par de realce do item
    ativo do menu — tanto para o modo claro quanto para o bloco ".dark" do
    modo escuro. Fundo do menu, da barra superior e cor do texto são fixos
    (tokens --bg-surface e --text-primary do app.css).
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
        $darkSurface = \App\Models\SiteSetting::darkBgFor($appearance->primary_color);
        $darkBody = \App\Models\SiteSetting::darkBodyBgFor($appearance->primary_color);
        $darkElevated = \App\Models\SiteSetting::darkElevatedBgFor($appearance->primary_color);
        $darkHover = \App\Models\SiteSetting::darkHoverBgFor($appearance->primary_color);
        // Menu lateral sempre claro no modo claro: item ativo = fundo clarinho
        // da cor de destaque + texto no tom escuro dela.
        $sidebarActiveBg = $appearance->primaryLightColor();
        $sidebarActiveText = $appearance->primaryHoverColor();
    @endphp
    <!-- Variáveis CSS de Aparência -->
    <style>
        html:root {
            --admin-primary: {{ $appearance->primary_color }};
            --admin-primary-dark: {{ $appearance->primaryHoverColor() }};
            --admin-primary-light: {{ $appearance->primaryLightColor() }};
            --admin-sidebar-active-bg: {{ $sidebarActiveBg }};
            --admin-sidebar-active-text: {{ $sidebarActiveText }};
            --focus-ring: var(--admin-primary);
        }
        /* "html.dark" (não só ".dark") de propósito: --bg-base/-surface/-card/
           -border-color (e -elevated/-hover/-input/-subtle) também têm valor
           padrão no ":root" do app.css — ":root" e ".dark" têm a MESMA
           especificidade, então sem o "html" extra aqui essa regra perderia
           pro ":root" do app.css (carregado depois pelo Vite). */
        html.dark {
            --admin-primary-dark: {{ $appearance->darkActiveTextColor() }};
            --admin-primary-light: {{ $appearance->darkActiveBgColor() }};
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
    class="site-dark-bg font-sans antialiased bg-slate-100"
    style="color: var(--text-primary);"
    x-data="{ sidebarCollapsed: localStorage.getItem('adminSidebarCollapsed') !== 'false', mobileSidebarOpen: false }"
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
