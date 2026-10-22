<?php

namespace App\Livewire\Admin;

use App\Enums\UserStatus;
use App\Livewire\Concerns\ComputesWeeklyTrend;
use App\Models\Article;
use App\Models\Comment;
use App\Models\ServiceLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

/**
 * Painel inicial do admin: reúne estatísticas e gráficos (artigos, usuários,
 * serviços) de acordo com as permissões do usuário logado.
 */
class Dashboard extends Component
{
    use ComputesWeeklyTrend, WithoutUrlPagination, WithPagination;

    /**
     * Quantos meses o gráfico "Notícias" (visitas x curtidas) mostra —
     * alternado pelo próprio card, entre 6 e 12 meses.
     */
    public int $newsRange = 12;

    /**
     * Critério de ordenação da tabela "Últimos Artigos" — alternado pelo
     * próprio card, entre mais recentes e mais visualizados.
     */
    public string $articlesSort = 'recent';

    public function setNewsRange(int $months): void
    {
        $this->newsRange = in_array($months, [6, 12], true) ? $months : 12;
    }

    public function setArticlesSort(string $sort): void
    {
        $this->articlesSort = in_array($sort, ['recent', 'popular'], true) ? $sort : 'recent';
        $this->resetPage('articlesPage');
    }

    /**
     * Isolada num island (ver dashboard.blade.php): precisa estar acessível
     * via $this-> porque islands não têm acesso a variáveis de view vindas
     * de fora deles, só a propriedades/métodos do componente.
     */
    #[Computed]
    public function recentArticles()
    {
        if (! auth()->user()->can('articles.view')) {
            return collect();
        }

        return Article::with(['categories', 'author'])
            ->withCount(['comments', 'likedByUsers'])
            ->when(
                $this->articlesSort === 'popular',
                fn ($query) => $query->orderByDesc('views_count'),
                fn ($query) => $query->latest('published_at'),
            )
            ->paginate(5, pageName: 'articlesPage');
    }

    public function render()
    {
        $user = auth()->user();
        $canViewContent = $user->can('articles.view');
        $canViewUsers = $user->can('users.view');
        $canViewServices = $user->can('services.view');

        $monthLabels = collect(range(11, 0))
            ->map(fn ($monthsAgo) => now()->subMonths($monthsAgo))
            ->map(fn ($date) => ['label' => ucfirst($date->translatedFormat('M')), 'year' => $date->year, 'month' => $date->month]);

        $newsMonthLabels = $monthLabels->slice(-$this->newsRange)->values();

        $emptyTrend = $this->emptyWeeklyTrend();

        return view('livewire.admin.dashboard', [
            'canViewContent' => $canViewContent,
            'canViewUsers' => $canViewUsers,
            'canViewServices' => $canViewServices,

            'likesTrend' => $canViewContent ? $this->weeklyTrend(DB::table('likes')->where('likeable_type', 'article'), 'created_at') : $emptyTrend,

            'totalArticles' => $canViewContent ? Article::count() : 0,
            'publishedArticles' => $canViewContent ? Article::published()->count() : 0,
            'draftArticles' => $canViewContent ? Article::where('is_published', false)->count() : 0,
            'totalLikes' => $canViewContent ? DB::table('likes')->where('likeable_type', 'article')->count() : 0,
            'recentComments' => $canViewContent ? Comment::with(['author', 'article'])->latest()->take(10)->get() : collect(),

            'newsMonthLabels' => $newsMonthLabels->pluck('label')->all(),
            'newsViewsByMonth' => $canViewContent
                ? $newsMonthLabels->map(fn ($m) => (int) Article::whereYear('published_at', $m['year'])->whereMonth('published_at', $m['month'])->sum('views_count'))->all()
                : [],
            'newsLikesByMonth' => $canViewContent
                ? $newsMonthLabels->map(fn ($m) => DB::table('likes')->where('likeable_type', 'article')->whereYear('created_at', $m['year'])->whereMonth('created_at', $m['month'])->count())->all()
                : [],
            'newsRange' => $this->newsRange,

            'totalUsers' => $canViewUsers ? User::count() : 0,
            'activeUsers' => $canViewUsers ? User::where('status', UserStatus::Active)->count() : 0,
            'blockedUsersList' => $canViewUsers ? User::where('status', UserStatus::Blocked)->with('roles')->take(5)->get() : collect(),
            'adminUsers' => $canViewUsers ? User::whereHas('roles', fn ($q) => $q->whereIn('name', ['Super Administrador', 'Administrador']))->count() : 0,

            'totalServices' => $canViewServices ? ServiceLink::count() : 0,
            'activeServices' => $canViewServices ? ServiceLink::where('is_active', true)->count() : 0,
            'inactiveServices' => $canViewServices ? ServiceLink::where('is_active', false)->count() : 0,
        ])->layout('layouts.admin', ['title' => 'Painel']);
    }
}
