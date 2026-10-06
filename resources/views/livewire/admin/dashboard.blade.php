{{--
    Painel principal (Dashboard) do admin.
    Mostra banner de boas-vindas, cards de estatísticas, gráficos de
    atividade e listas de "últimos artigos" / "usuários bloqueados".
    Cada bloco só aparece se o usuário logado tiver a permissão
    correspondente (conteúdo, usuários ou serviços).
--}}
<div class="space-y-4">
    <!-- Welcome Banner -->
    <x-admin.welcome-banner
        :name="auth()->user()->name"
        message="Aqui está um resumo do painel administrativo hoje."
        :badge="$canViewContent && $draftArticles > 0 ? $draftArticles.' rascunho(s) pendente(s)' : null"
    />
    <!-- Estatísticas (Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        @if ($canViewContent)
            <x-admin.stat-card
                label="Artigos Publicados" :value="$publishedArticles" :sublabel="$totalArticles.' no total'"
                icon="newspaper" iconColor="#2563EB"
                :progress="$totalArticles > 0 ? ($publishedArticles / $totalArticles) * 100 : 0"
            />
            <x-admin.stat-card
                label="Curtidas Totais" :value="$totalLikes" sublabel="Em todos os artigos"
                icon="heart" iconColor="#EC4899"
            />
        @endif
        @if ($canViewUsers)
            <x-admin.stat-card
                label="Total de Usuários" :value="$totalUsers" :sublabel="$activeUsers.' ativo(s)'"
                icon="users" iconColor="#059669"
                :progress="$totalUsers > 0 ? ($activeUsers / $totalUsers) * 100 : 0"
            />
        @endif
        @if ($canViewServices)
            <x-admin.stat-card
                label="Serviços Ativos" :value="$activeServices" :sublabel="$totalServices.' no total'"
                icon="sliders" iconColor="#F59E0B"
                :progress="$totalServices > 0 ? ($activeServices / $totalServices) * 100 : 0"
            />
        @endif
    </div>
    <!-- Gráficos e Resumos (se tiver permissão) -->
    @if ($canViewContent)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <x-ui.card class="lg:col-span-2">
                <div class="flex items-center gap-2 mb-4">
                    <i class="fa-solid fa-chart-line" style="color: var(--admin-primary);"></i>
                    <span class="font-semibold" style="color: var(--text-primary);">Atividade do Mural</span>
                    <span class="text-xs" style="color: var(--text-muted);">— artigos publicados por mês</span>
                </div>
                <x-admin.line-chart :labels="$articleActivityLabels" :values="$articleActivityValues" />
            </x-ui.card>

            <x-ui.card>
                <div class="font-semibold mb-4" style="color: var(--text-primary);">Resumo</div>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span style="color: var(--text-secondary);">Artigos publicados</span>
                        <span class="font-semibold" style="color: var(--text-primary);">{{ $publishedArticles }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span style="color: var(--text-secondary);">Rascunhos</span>
                        <span class="font-semibold" style="color: var(--text-primary);">{{ $draftArticles }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span style="color: var(--text-secondary);">Categorias</span>
                        <span class="font-semibold" style="color: var(--text-primary);">{{ $totalCategories }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span style="color: var(--text-secondary);">Curtidas totais</span>
                        <span class="font-semibold" style="color: var(--text-primary);">{{ $totalLikes }}</span>
                    </div>
                </div>
            </x-ui.card>
        </div>
    @endif
    <!-- Gráficos de usuários e serviços -->
    @if ($canViewUsers || $canViewServices)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @if ($canViewUsers)
                <x-ui.card>
                    <div class="flex items-center gap-2 mb-4">
                        <i class="fa-solid fa-user-group" style="color: var(--admin-primary);"></i>
                        <span class="font-semibold" style="color: var(--text-primary);">Novos usuários por mês</span>
                    </div>
                    <x-admin.bar-chart :labels="$newUsersLabels" :values="$newUsersValues" />
                </x-ui.card>
            @endif

            @if ($canViewServices)
                <x-ui.card>
                    <div class="font-semibold mb-4" style="color: var(--text-primary);">Serviços</div>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span style="color: var(--text-secondary);">Total de serviços</span>
                            <span class="font-semibold" style="color: var(--text-primary);">{{ $totalServices }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span style="color: var(--text-secondary);">Ativos</span>
                            <span class="font-semibold" style="color: var(--text-primary);">{{ $activeServices }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span style="color: var(--text-secondary);">Inativos</span>
                            <span class="font-semibold" style="color: var(--text-primary);">{{ $inactiveServices }}</span>
                        </div>
                    </div>
                </x-ui.card>
            @endif
        </div>
    @endif

    <!-- Últimos Artigos (se tiver permissão) -->
    @if ($canViewContent)
        <x-ui.card :padding="false">
            <div class="px-6 py-4 flex items-center justify-between" style="border-bottom: 1px solid var(--border-color);">
                <span class="font-semibold" style="color: var(--text-primary);">Últimos Artigos</span>
                <a href="{{ route('admin.articles.index') }}" class="text-sm text-emerald-700 hover:underline">Ver todos</a>
            </div>
            @if ($recentArticles->isEmpty())
                <x-ui.empty-state title="Nenhum artigo publicado ainda." icon="newspaper" />
            @else
                <div class="divide-y" style="border-color: var(--border-color);">
                    @foreach ($recentArticles as $article)
                        <div class="px-6 py-3 flex items-center justify-between gap-4" style="border-color: var(--border-color);">
                            <div class="min-w-0">
                                <div class="font-medium truncate" style="color: var(--text-primary);">{{ $article->title }}</div>
                                <div class="text-xs" style="color: var(--text-muted);">{{ $article->category?->name ?? '—' }} · {{ $article->created_at->format('d/m/Y') }}</div>
                            </div>
                            <x-ui.badge :variant="$article->is_published ? 'success' : 'warning'">{{ $article->is_published ? 'Publicado' : 'Rascunho' }}</x-ui.badge>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>
    @endif
    <!-- Tabela de usuários bloqueados (se tiver permissão) -->
    @if ($canViewUsers)
        <x-ui.card :padding="false">
            <div class="px-6 py-4 flex items-center justify-between" style="border-bottom: 1px solid var(--border-color);">
                <span class="font-semibold" style="color: var(--text-primary);">Usuários Bloqueados</span>
                <a href="{{ route('admin.users.index') }}" class="text-sm text-emerald-700 hover:underline">Ver todos</a>
            </div>
            @if ($blockedUsersList->isEmpty())
                <x-ui.empty-state title="Nenhum usuário bloqueado." description="Todos os usuários estão com acesso ativo." icon="check-circle" />
            @else
                <div class="divide-y" style="border-color: var(--border-color);">
                    @foreach ($blockedUsersList as $blockedUser)
                        <div class="px-6 py-3 flex items-center justify-between gap-4" style="border-color: var(--border-color);">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <x-ui.avatar :name="$blockedUser->name" size="sm" />
                                <div class="min-w-0">
                                    <div class="font-medium truncate" style="color: var(--text-primary);">{{ $blockedUser->name }}</div>
                                    <div class="text-xs truncate" style="color: var(--text-muted);">{{ $blockedUser->email }}</div>
                                </div>
                            </div>
                            <x-ui.badge variant="danger">Bloqueado</x-ui.badge>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>
    @endif
</div>
