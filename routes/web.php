<?php

use App\Livewire\Admin\ActivityLogIndex;
use App\Livewire\Admin\Appearance\AppearanceForm;
use App\Livewire\Admin\Articles\ArticleForm;
use App\Livewire\Admin\Articles\ArticleIndex;
use App\Livewire\Admin\Birthdays\BirthdayBannerIndex;
use App\Livewire\Admin\Categories\CategoryIndex;
use App\Livewire\Admin\Council\CouncilMemberIndex;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Documents\DocumentIndex;
use App\Livewire\Admin\Events\EventIndex;
use App\Livewire\Admin\Forum\AdminForumIndex;
use App\Livewire\Admin\MediaLibraryIndex;
use App\Livewire\Admin\Photos\PhotoAlbumIndex;
use App\Livewire\Admin\Roles\RoleIndex;
use App\Livewire\Admin\Services\ServiceLinkIndex;
use App\Livewire\Admin\Trainings\TrainingIndex;
use App\Livewire\Admin\Users\UserIndex;
use App\Livewire\Admin\Videos\VideoIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Birthdays\BirthdaysIndex;
use App\Livewire\Council\CouncilIndex;
use App\Livewire\Documents\DocumentsIndex;
use App\Livewire\Events\EventsIndex;
use App\Livewire\Forum\ForumIndex;
use App\Livewire\Forum\ForumShow;
use App\Livewire\Home\HomeIndex;
use App\Livewire\Mural\ArticleShow;
use App\Livewire\Mural\MuralIndex;
use App\Livewire\Noticias\NoticiasIndex;
use App\Livewire\Photos\PhotoAlbumShow;
use App\Livewire\Photos\PhotosIndex;
use App\Livewire\Services\ServicesIndex;
use App\Livewire\Sos\SosIndex;
use App\Livewire\Trainings\TrainingsIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Login: só acessível para quem ainda não está autenticado.
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

// Logout: invalida a sessão e gera um novo token CSRF antes de voltar ao login.
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

// Página inicial da intranet, após o login.
Route::get('/inicio', HomeIndex::class)
    ->middleware('auth')
    ->name('inicio');

// Dashboard do painel administrativo (requer login + ser admin).
Route::get('/dashboard', Dashboard::class)->middleware(['auth', 'admin'])->name('dashboard');

// Rotas públicas do site (visíveis para qualquer usuário logado, sem permissão específica).
Route::get('/servicos', ServicesIndex::class)
    ->middleware('auth')
    ->name('services.index');

Route::get('/mural', MuralIndex::class)
    ->middleware('auth')
    ->name('mural.index');

Route::get('/mural/{article}', ArticleShow::class)
    ->middleware('auth')
    ->name('mural.show');

Route::get('/documentos', DocumentsIndex::class)
    ->middleware('auth')
    ->name('documents.index');

Route::get('/conselho-superior', CouncilIndex::class)
    ->middleware('auth')
    ->name('council.index');

Route::get('/fotos', PhotosIndex::class)
    ->middleware('auth')
    ->name('photos.index');

Route::get('/fotos/{album}', PhotoAlbumShow::class)
    ->middleware('auth')
    ->name('photos.show');

Route::redirect('/videos', '/fotos');

Route::get('/eventos', EventsIndex::class)
    ->middleware('auth')
    ->name('events.index');

Route::get('/capacitacoes', TrainingsIndex::class)
    ->middleware('auth')
    ->name('trainings.index');

Route::get('/noticias', NoticiasIndex::class)
    ->middleware('auth')
    ->name('noticias.index');

Route::get('/sos', SosIndex::class)
    ->middleware('auth')
    ->name('sos.index');

Route::get('/aniversariantes', BirthdaysIndex::class)
    ->middleware('auth')
    ->name('birthdays.index');

Route::get('/forum', ForumIndex::class)
    ->middleware('auth')
    ->name('forum.index');

Route::get('/forum/{topic}', ForumShow::class)
    ->middleware('auth')
    ->name('forum.show');

// Páginas "em construção": gera automaticamente uma rota de placeholder para
// cada item do menu público (config/sidebar.php) ainda não implementado
// (identificado pelo prefixo "placeholder." no nome da rota).
Route::middleware('auth')->group(function () {
    foreach (config('sidebar') as $section) {
        foreach ($section['items'] ?? [$section] as $item) {
            if (! str_starts_with($item['route'], 'placeholder.')) {
                continue;
            }

            Route::get($item['uri'], fn () => view('coming-soon', ['title' => $item['label']]))
                ->name($item['route']);
        }
    }
});

// Painel administrativo — cada grupo abaixo exige login + ser admin, mais uma
// permissão específica (via middleware "permission:") para a seção em questão.
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::middleware('permission:users.view')->group(function () {
        Route::get('/usuarios', UserIndex::class)->name('users.index');
    });

    Route::middleware('permission:roles.manage')->group(function () {
        Route::get('/papeis-e-permissoes', RoleIndex::class)->name('roles.index');
    });

    Route::middleware('permission:forum.view')->group(function () {
        Route::get('/forum', AdminForumIndex::class)->name('forum.index');
    });

    Route::middleware('permission:services.view')->group(function () {
        Route::get('/servicos', ServiceLinkIndex::class)->name('services.index');
    });

    Route::middleware('permission:council.view')->group(function () {
        Route::get('/conselho-superior', CouncilMemberIndex::class)->name('council.index');
    });

    Route::middleware('permission:articles.view')->group(function () {
        Route::get('/noticias', ArticleIndex::class)->name('articles.index');
        Route::get('/noticias/novo', ArticleForm::class)->name('articles.create');
        Route::get('/noticias/{article}/editar', ArticleForm::class)->name('articles.edit');
    });

    Route::middleware('permission:categories.view')->group(function () {
        Route::get('/categorias', CategoryIndex::class)->name('categories.index');
    });

    Route::middleware('permission:media.view')->group(function () {
        Route::get('/biblioteca-de-midia', MediaLibraryIndex::class)->name('media.index');
    });

    Route::middleware('permission:documents.view')->group(function () {
        Route::get('/documentos', DocumentIndex::class)->name('documents.index');
    });

    Route::middleware('permission:photos.view')->group(function () {
        Route::get('/fotos', PhotoAlbumIndex::class)->name('photos.index');
    });

    Route::middleware('permission:videos.view')->group(function () {
        Route::get('/videos', VideoIndex::class)->name('videos.index');
    });

    Route::middleware('permission:events.view')->group(function () {
        Route::get('/eventos', EventIndex::class)->name('events.index');
    });

    Route::middleware('permission:trainings.view')->group(function () {
        Route::get('/capacitacoes', TrainingIndex::class)->name('trainings.index');
    });

    Route::middleware('permission:birthday-banners.view')->group(function () {
        Route::get('/banners-aniversariantes', BirthdayBannerIndex::class)->name('birthday-banners.index');
    });

    Route::middleware('permission:appearance.view')->group(function () {
        Route::get('/aparencia', AppearanceForm::class)->name('appearance.edit');
    });

    Route::middleware('permission:activity-log.view')->group(function () {
        Route::get('/historico-de-atividades', ActivityLogIndex::class)->name('activity-log.index');
    });

    // Página "em construção" do item "Assistente Geral" no menu admin.
    Route::get('/assistente-geral', fn () => view('admin.coming-soon', ['title' => 'Assistente Geral']))
        ->name('placeholder.assistente-geral');
});

// Raiz do site: manda quem já está logado para "/inicio" e quem não está para o login.
Route::get('/', function () {
    return auth()->check() ? redirect()->route('inicio') : redirect()->route('login');
});
