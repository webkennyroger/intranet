<?php

namespace App\Livewire\Admin;

use App\Enums\UserStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\ServiceLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Painel inicial do admin: reúne estatísticas e gráficos (artigos, usuários,
 * serviços) de acordo com as permissões do usuário logado.
 */
class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();
        $canViewContent = $user->can('content.view');
        $canViewUsers = $user->can('users.view');
        $canViewServices = $user->can('services.manage');

        $monthLabels = collect(range(11, 0))
            ->map(fn ($monthsAgo) => now()->subMonths($monthsAgo))
            ->map(fn ($date) => ['label' => ucfirst($date->translatedFormat('M')), 'year' => $date->year, 'month' => $date->month]);

        return view('livewire.admin.dashboard', [
            'canViewContent' => $canViewContent,
            'canViewUsers' => $canViewUsers,
            'canViewServices' => $canViewServices,

            'totalArticles' => $canViewContent ? Article::count() : 0,
            'publishedArticles' => $canViewContent ? Article::published()->count() : 0,
            'draftArticles' => $canViewContent ? Article::where('is_published', false)->count() : 0,
            'totalLikes' => $canViewContent ? DB::table('article_likes')->count() : 0,
            'totalCategories' => $canViewContent ? Category::count() : 0,
            'recentArticles' => $canViewContent ? Article::with(['category', 'author'])->latest()->take(5)->get() : collect(),

            'articleActivityLabels' => $monthLabels->pluck('label')->all(),
            'articleActivityValues' => $canViewContent
                ? $monthLabels->map(fn ($m) => Article::whereYear('published_at', $m['year'])->whereMonth('published_at', $m['month'])->count())->all()
                : [],

            'totalUsers' => $canViewUsers ? User::count() : 0,
            'activeUsers' => $canViewUsers ? User::where('status', UserStatus::Active)->count() : 0,
            'blockedUsersList' => $canViewUsers ? User::where('status', UserStatus::Blocked)->with('roles')->take(5)->get() : collect(),
            'adminUsers' => $canViewUsers ? User::whereHas('roles', fn ($q) => $q->whereIn('name', ['Super Administrador', 'Administrador']))->count() : 0,
            'newUsersLabels' => $monthLabels->slice(-6)->pluck('label')->all(),
            'newUsersValues' => $canViewUsers
                ? $monthLabels->slice(-6)->map(fn ($m) => User::whereYear('created_at', $m['year'])->whereMonth('created_at', $m['month'])->count())->all()
                : [],

            'totalServices' => $canViewServices ? ServiceLink::count() : 0,
            'activeServices' => $canViewServices ? ServiceLink::where('is_active', true)->count() : 0,
            'inactiveServices' => $canViewServices ? ServiceLink::where('is_active', false)->count() : 0,
        ])->layout('layouts.admin', ['title' => 'Painel']);
    }
}
