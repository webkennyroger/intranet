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
    <!-- Estatísticas: um cartão só, ocupando a largura toda, dividido por
         uma linha fina — mesmo padrão usado nas outras páginas do admin. -->
    <div class="ui-card flex flex-wrap items-stretch divide-x divide-(--border-color)">
        @if ($canViewContent)
            <div class="flex items-center gap-3 px-5 py-4 flex-1 min-w-48">
                <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #2563EB 14%, transparent);">
                    <i class="fa-solid fa-newspaper text-sm" style="color: #2563EB;"></i>
                </span>
                <div class="whitespace-nowrap">
                    <p class="text-[10px] font-bold uppercase tracking-wide leading-none" style="color: var(--text-muted);">Artigos Publicados</p>
                    <p class="text-xl font-black leading-tight" style="color: var(--text-primary);">{{ $publishedArticles }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3 px-5 py-4 flex-1 min-w-48">
                <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #EC4899 14%, transparent);">
                    <i class="fa-solid fa-heart text-sm" style="color: #EC4899;"></i>
                </span>
                <div class="whitespace-nowrap">
                    <p class="text-[10px] font-bold uppercase tracking-wide leading-none" style="color: var(--text-muted);">Curtidas Totais</p>
                    <p class="text-xl font-black leading-tight" style="color: var(--text-primary);">{{ $totalLikes }}</p>
                </div>
            </div>
        @endif
        @if ($canViewUsers)
            <div class="flex items-center gap-3 px-5 py-4 flex-1 min-w-48">
                <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #059669 14%, transparent);">
                    <i class="fa-solid fa-users text-sm" style="color: #059669;"></i>
                </span>
                <div class="whitespace-nowrap">
                    <p class="text-[10px] font-bold uppercase tracking-wide leading-none" style="color: var(--text-muted);">Total de Usuários</p>
                    <p class="text-xl font-black leading-tight" style="color: var(--text-primary);">{{ $totalUsers }}</p>
                </div>
            </div>
        @endif
        @if ($canViewServices)
            <div class="flex items-center gap-3 px-5 py-4 flex-1 min-w-48">
                <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #F59E0B 14%, transparent);">
                    <i class="fa-solid fa-sliders text-sm" style="color: #F59E0B;"></i>
                </span>
                <div class="whitespace-nowrap">
                    <p class="text-[10px] font-bold uppercase tracking-wide leading-none" style="color: var(--text-muted);">Serviços Ativos</p>
                    <p class="text-xl font-black leading-tight" style="color: var(--text-primary);">{{ $activeServices }}</p>
                </div>
            </div>
        @endif
    </div>
    <!-- Gráficos e Resumos (se tiver permissão) -->
    @if ($canViewContent)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Notícias: curtidas em destaque + visitas x curtidas por mês -->
            <div class="ui-card h-full flex flex-col p-6 lg:col-span-2" style="border-top: 2px solid #06B6D4;">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #06B6D4 14%, transparent); color: #06B6D4;">
                            <i class="fa-solid fa-newspaper text-sm"></i>
                        </span>
                        <div>
                            <div class="font-bold" style="color: var(--text-primary);">Notícias</div>
                            <div class="text-xs" style="color: var(--text-muted);">Visitas e curtidas por mês</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 p-1 rounded-full shrink-0" style="background-color: var(--bg-elevated);">
                        <button type="button" wire:click="setNewsRange(6)" class="px-3 py-1 rounded-full text-xs font-semibold transition {{ $newsRange === 6 ? 'shadow-sm' : '' }}" style="{{ $newsRange === 6 ? 'background-color: var(--bg-card); color: var(--text-primary);' : 'color: var(--text-secondary);' }}">6 meses</button>
                        <button type="button" wire:click="setNewsRange(12)" class="px-3 py-1 rounded-full text-xs font-semibold transition {{ $newsRange === 12 ? 'shadow-sm' : '' }}" style="{{ $newsRange === 12 ? 'background-color: var(--bg-card); color: var(--text-primary);' : 'color: var(--text-secondary);' }}">12 meses</button>
                    </div>
                </div>

                <div class="flex-1 min-h-0 flex flex-col sm:flex-row gap-6 mt-5">
                    <!-- Curtidas: número de destaque + tendência -->
                    <div class="sm:w-40 shrink-0 flex flex-col justify-between">
                        <div>
                            <div class="text-2xl font-bold tabular-nums" style="color: var(--text-primary);">{{ $totalLikes }}</div>
                            <div class="text-xs" style="color: var(--text-muted);">curtidas no total</div>

                            <div class="mt-2 flex items-center gap-2">
                                <x-admin.mini-sparkline :values="$likesTrend['values']" color="#8B5CF6" />
                                <span class="text-xs font-bold inline-flex items-center gap-1" style="color: {{ $likesTrend['changePercent'] >= 0 ? '#059669' : '#DC2626' }};">
                                    <i class="fa-solid fa-arrow-{{ $likesTrend['changePercent'] >= 0 ? 'up' : 'down' }} text-[9px]"></i>{{ number_format(abs($likesTrend['changePercent']), 1) }}%
                                </span>
                            </div>
                        </div>

                        <a href="{{ route('admin.articles.index') }}" class="mt-4 inline-flex items-center justify-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold transition hover:bg-slate-50 dark:hover:bg-white/5" style="border: 1px solid var(--border-color); color: var(--text-primary);">
                            Ver notícias
                        </a>
                    </div>

                    <!-- Visitas x Curtidas por mês -->
                    <div class="flex-1 min-w-0 flex flex-col">
                        <div class="flex-1 min-h-36">
                            <x-admin.grouped-bar-chart
                                :labels="$newsMonthLabels"
                                :series="[
                                    ['label' => 'Visitas', 'color' => '#06B6D4', 'values' => $newsViewsByMonth],
                                    ['label' => 'Curtidas', 'color' => '#8B5CF6', 'values' => $newsLikesByMonth],
                                ]"
                            />
                        </div>

                        <div class="flex items-center justify-center gap-4 mt-3 text-xs shrink-0" style="color: var(--text-secondary);">
                            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background-color: #06B6D4;"></span>Visitas</span>
                            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background-color: #8B5CF6;"></span>Curtidas</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comentários Recentes -->
            <div class="ui-card h-full flex flex-col" style="border-top: 2px solid #8B5CF6;">
                <div class="px-5 py-4 flex items-center gap-3 shrink-0" style="border-bottom: 1px solid var(--border-color);">
                    <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #8B5CF6 14%, transparent); color: #8B5CF6;">
                        <i class="fa-solid fa-comments text-sm"></i>
                    </span>
                    <div>
                        <div class="font-bold" style="color: var(--text-primary);">Comentários Recentes</div>
                        <div class="text-xs" style="color: var(--text-muted);">O que os servidores estão comentando</div>
                    </div>
                </div>
                @if ($recentComments->isEmpty())
                    <div class="flex-1 flex items-center justify-center p-6">
                        <x-ui.empty-state title="Nenhum comentário ainda." icon="comment" />
                    </div>
                @else
                    <div class="sidebar-card max-h-96 overflow-y-auto p-2 space-y-1">
                        @foreach ($recentComments as $comment)
                            <a href="{{ route('mural.show', $comment->article) }}" class="flex items-start gap-3 rounded-xl p-3 transition hover:bg-slate-50 dark:hover:bg-white/5">
                                <x-ui.avatar :name="$comment->author?->name ?? '—'" size="sm" />
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-baseline justify-between gap-2">
                                        <p class="text-sm font-semibold truncate" style="color: var(--text-primary);">{{ $comment->author?->name ?? 'Usuário' }}</p>
                                        <span class="text-[11px] shrink-0" style="color: var(--text-muted);">{{ $comment->created_at->locale('pt_BR')->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-xs mt-1 italic line-clamp-2" style="color: var(--text-secondary);">&ldquo;{{ $comment->body }}&rdquo;</p>
                                    <p class="text-[11px] mt-1.5 truncate inline-flex items-center gap-1.5" style="color: var(--text-muted);">
                                        <i class="fa-solid fa-newspaper text-[9px] opacity-70"></i>{{ $comment->article->title }}
                                    </p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif
    {{-- Últimos Artigos — isolado num island: paginar/trocar a ordenação só
         atualiza esta região (ver recentArticles() no Dashboard), sem
         re-renderizar o resto do painel (cards, gráfico, comentários). Por
         isso o controle de permissão fica DENTRO do island (um island não
         pode ficar dentro de @if/@foreach — ver docs do Livewire 4). --}}
    @island(name: 'recent-articles', always: true)
        @if (auth()->user()->can('articles.view'))
            <div class="ui-card overflow-hidden" style="border-top: 2px solid #2563EB;">
                <div class="p-5 flex items-center justify-between gap-3 flex-wrap" style="border-bottom: 1px solid var(--border-color);">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #2563EB 14%, transparent); color: #2563EB;">
                            <i class="fa-solid fa-newspaper text-sm"></i>
                        </span>
                        <div>
                            <div class="font-bold" style="color: var(--text-primary);">Últimos Artigos</div>
                            <div class="text-xs" style="color: var(--text-muted);">Publicados no mural</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1 p-1 rounded-full shrink-0" style="background-color: var(--bg-elevated);">
                            <button type="button" wire:click.preserve-scroll="setArticlesSort('recent')" class="px-3 py-1 rounded-full text-xs font-semibold transition {{ $this->articlesSort === 'recent' ? 'shadow-sm' : '' }}" style="{{ $this->articlesSort === 'recent' ? 'background-color: var(--bg-card); color: var(--text-primary);' : 'color: var(--text-secondary);' }}">Recentes</button>
                            <button type="button" wire:click.preserve-scroll="setArticlesSort('popular')" class="px-3 py-1 rounded-full text-xs font-semibold transition {{ $this->articlesSort === 'popular' ? 'shadow-sm' : '' }}" style="{{ $this->articlesSort === 'popular' ? 'background-color: var(--bg-card); color: var(--text-primary);' : 'color: var(--text-secondary);' }}">Mais vistos</button>
                        </div>
                        <a href="{{ route('admin.articles.index') }}" class="inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-sm font-semibold text-white shrink-0 transition hover:brightness-110" style="background-color: var(--admin-primary, var(--brand-primary, #0C4A34));">
                            Ver todos
                        </a>
                    </div>
                </div>

                @if ($this->recentArticles->isEmpty())
                    <x-ui.empty-state title="Nenhum artigo publicado ainda." icon="newspaper" />
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead>
                                <tr class="text-xs font-semibold uppercase tracking-wide" style="background-color: var(--bg-elevated); border-bottom: 1px solid var(--border-color); color: var(--text-secondary);">
                                    <th class="px-6 py-3 w-10">#</th>
                                    <th class="px-4 py-3"><i class="fa-solid fa-newspaper mr-1 opacity-60"></i> Artigo</th>
                                    <th class="px-4 py-3"><i class="fa-solid fa-tag mr-1 opacity-60"></i> Categoria</th>
                                    <th class="px-4 py-3"><i class="fa-solid fa-calendar-days mr-1 opacity-60"></i> Data</th>
                                    <th class="px-4 py-3 text-center"><i class="fa-solid fa-comment mr-1 opacity-60"></i> Comentários</th>
                                    <th class="px-4 py-3 text-center"><i class="fa-solid fa-heart mr-1 opacity-60"></i> Curtidas</th>
                                    <th class="px-4 py-3 text-center"><i class="fa-solid fa-eye mr-1 opacity-60"></i> Visualizações</th>
                                    <th class="px-4 py-3"><i class="fa-solid fa-circle-check mr-1 opacity-60"></i> Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-(--border-color) [&>tr]:transition-colors [&>tr:hover]:bg-(--admin-primary)/4" style="color: var(--text-primary);">
                                @foreach ($this->recentArticles as $article)
                                    @php $category = $article->categories->first(); @endphp
                                    <tr wire:key="dashboard-article-{{ $article->id }}">
                                        <td class="px-6 py-3" style="color: var(--text-muted);">{{ str_pad($loop->iteration + ($this->recentArticles->currentPage() - 1) * $this->recentArticles->perPage(), 2, '0', STR_PAD_LEFT) }}</td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('admin.articles.edit', $article) }}" class="flex items-center gap-3 group min-w-0">
                                                <span class="w-10 h-10 rounded-lg shrink-0 flex items-center justify-center overflow-hidden" style="background-color: {{ $article->cover_color }};">
                                                    @if ($article->featuredImageUrl())
                                                        <img src="{{ $article->featuredImageUrl() }}" alt="{{ $article->title }}" class="w-full h-full object-cover" />
                                                    @else
                                                        <i class="fa-solid fa-{{ $article->cover_icon }} text-white/80"></i>
                                                    @endif
                                                </span>
                                                <span class="font-medium truncate max-w-64 group-hover:underline" style="color: var(--text-primary);">{{ $article->title }}</span>
                                            </a>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($category)
                                                <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded" style="background-color: {{ $category->lightColor() }}; color: {{ $category->color }};">{{ $category->name }}</span>
                                            @else
                                                <span style="color: var(--text-muted);">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap" style="color: var(--text-secondary);">{{ $article->published_at?->format('d/m/Y') ?? $article->created_at->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3 text-center" style="color: var(--text-secondary);">{{ $article->comments_count }}</td>
                                        <td class="px-4 py-3 text-center" style="color: var(--text-secondary);">{{ $article->liked_by_users_count }}</td>
                                        <td class="px-4 py-3 text-center" style="color: var(--text-secondary);">{{ $article->views_count }}</td>
                                        <td class="px-4 py-3"><x-ui.badge :variant="$article->is_published ? 'success' : 'warning'">{{ $article->is_published ? 'Publicado' : 'Rascunho' }}</x-ui.badge></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4" style="border-top: 1px solid var(--border-color);">
                        {{ $this->recentArticles->links() }}
                    </div>
                @endif
            </div>
        @endif
    @endisland
</div>
