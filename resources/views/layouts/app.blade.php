{{--
    Layout-base das páginas do site público (área logada fora do admin):
    header fixo no topo, sidebar lateral e área de conteúdo (slot).
    Define as variáveis CSS --brand-primary/--brand-primary-dark/-light a
    partir da cor Primary configurada em Aparência, com um bloco ".dark"
    próprio para o modo escuro (fundo, cards e itens da sidebar).
--}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Painel' }} — Intranet DPEMT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Variáveis CSS da marca (cores do site) -->
    @php
        $brand = \App\Models\SiteSetting::current();
        $darkSurface = \App\Models\SiteSetting::darkBgFor($brand->primary_color);
        $darkBody = \App\Models\SiteSetting::darkBodyBgFor($brand->primary_color);
    @endphp
    <style>
        :root {
            --brand-primary: {{ $brand->primary_color }};
            --brand-primary-dark: {{ $brand->primaryHoverColor() }};
            --brand-primary-light: {{ $brand->primaryLightColor() }};
        }
        /* No escuro, o item ativo usa o mesmo matiz da cor escolhida, só
           escurecido/clareado para continuar legível. O mesmo vale pro
           fundo da página e dos cards (variáveis do app.css, usadas pelos
           componentes compartilhados). Classes simples + seletor direto —
           a sintaxe dark:bg-(--var) do Tailwind não aplicava de forma
           confiável no navegador. */
        .dark {
            --brand-primary-dark: {{ $brand->darkActiveTextColor() }};
            --brand-primary-light: {{ $brand->darkActiveBgColor() }};
            --bg-base: {{ $darkBody }};
            --bg-surface: {{ $darkSurface }};
            --bg-card: {{ $darkSurface }}f2;
            --border-color: {{ $darkSurface }};
        }
        html.dark .sidebar-card { background-color: var(--bg-card); border-color: var(--border-color); }
        html.dark .sidebar-muted-bg { background-color: var(--border-color); }
        html.dark .sidebar-subtle-bg { background-color: var(--bg-base); }
        html.dark .sidebar-border-t { border-color: var(--border-color); }
        html.dark .sidebar-nav-item:hover { background-color: var(--border-color); }
    </style>
    @vite(['resources/css/app.css'])
    @livewireStyles
</head>
<body class="site-dark-bg h-full font-sans antialiased bg-slate-100 dark:text-[#e6f2ec] flex flex-col text-slate-800" x-data="{ sidebarCollapsed: false, mobileSidebarOpen: false }">
    <!-- Header -->
    <x-layout.header />

    <div class="flex flex-1 overflow-hidden relative">
        <!-- Overlay do menu mobile -->
        <div
            x-show="mobileSidebarOpen"
            x-cloak
            @click="mobileSidebarOpen = false"
            class="fixed inset-0 bg-black/40 z-40 lg:hidden"
        ></div>

        <!-- Sidebar -->
        <x-layout.sidebar />

        <!-- Conteúdo principal (slot) -->
        <main class="site-dark-bg flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 bg-slate-100">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
