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
        $darkElevated = \App\Models\SiteSetting::darkElevatedBgFor($brand->primary_color);
        $darkHover = \App\Models\SiteSetting::darkHoverBgFor($brand->primary_color);
    @endphp
    <style>
        :root {
            --brand-primary: {{ $brand->primary_color }};
            --brand-primary-dark: {{ $brand->primaryHoverColor() }};
            --brand-primary-light: {{ $brand->primaryLightColor() }};
            --sidebar-active-bg: {{ \App\Models\SiteSetting::lightActiveHighlightBgFor($brand->primary_color) }};
            --focus-ring: var(--brand-primary);
        }
        /* No escuro, o item ativo usa o mesmo matiz da cor escolhida, só
           escurecido/clareado para continuar legível. O mesmo vale pro
           fundo da página e dos cards (variáveis do app.css, usadas pelos
           componentes compartilhados). Classes simples + seletor direto —
           a sintaxe dark:bg-(--var) do Tailwind não aplicava de forma
           confiável no navegador. Essas variáveis não têm mais valor fixo
           no app.css — sem isso, a declaração estática carregada depois
           deste bloco (pelo Vite, mais abaixo) sempre ganhava do cascade e
           o modo escuro nunca acompanhava a cor escolhida aqui. */
        /* "html.dark" (não só ".dark") de propósito: --bg-base/-surface/
           -card/-border-color (e as novas -elevated/-hover/-input/-subtle)
           também têm valor padrão no ":root" do app.css — ":root" e
           ".dark" têm a MESMA especificidade, então sem o "html" extra
           aqui essa regra perderia pro ":root" do app.css (carregado
           depois) sempre que ambos baterem no mesmo elemento <html>. */
        html.dark {
            --brand-primary-dark: {{ $brand->darkActiveTextColor() }};
            --brand-primary-light: {{ $brand->darkActiveBgColor() }};
            --sidebar-active-bg: {{ $brand->darkActiveBgColor() }};
            --bg-base: {{ $darkBody }};
            --bg-surface: {{ $darkSurface }};
            --bg-elevated: {{ $darkElevated }};
            --bg-hover: {{ $darkHover }};
            --bg-input: {{ $darkHover }}80;
            --bg-card: {{ $darkSurface }};
            --border-color: {{ $darkElevated }};
            --border-subtle: {{ $darkElevated }}80;
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
<body
    class="site-dark-bg font-sans antialiased bg-slate-100 dark:text-(--text-primary) text-slate-800"
    x-data="{ sidebarCollapsed: localStorage.getItem('sidebarCollapsed') !== 'false', mobileSidebarOpen: false }"
    x-init="$watch('sidebarCollapsed', (value) => localStorage.setItem('sidebarCollapsed', value))"
>
    <!-- Header -->
    <x-layout.header />

    <!-- Overlay do menu mobile -->
    <div
        x-show="mobileSidebarOpen"
        x-cloak
        @click="mobileSidebarOpen = false"
        class="fixed inset-0 bg-black/40 z-40 lg:hidden"
    ></div>

    <!-- Sidebar (fixa, não acompanha o scroll da página) -->
    <x-layout.sidebar />

    <!-- Conteúdo principal (slot) -->
    <div class="flex flex-col min-h-[calc(100vh-4rem)] lg:ml-64 transition-all duration-300" :class="sidebarCollapsed ? 'lg:ml-26!' : ''">
        <main class="site-dark-bg flex-1 p-4 md:p-6 bg-slate-50">
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="text-center text-xs py-4 border-t" style="color: var(--text-muted); border-color: var(--border-color);">
            Copyright &copy; {{ now()->year }} Intranet DPEMT. Todos os direitos reservados.
        </footer>
    </div>

    <x-ui.confirm-modal />

    <!-- Notificações (toast) de toda ação de criar/editar/excluir/atualizar -->
    <x-ui.toast />

    @livewireScripts
</body>
</html>
