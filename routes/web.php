<?php

use App\Livewire\Admin\Articles\ArticleIndex;
use App\Livewire\Admin\Services\ServiceLinkIndex;
use App\Livewire\Admin\Users\UserIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Mural\ArticleShow;
use App\Livewire\Mural\MuralIndex;
use App\Livewire\Services\ServicesIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::get('/dashboard', fn () => view('dashboard'))
    ->middleware('auth')
    ->name('dashboard');

Route::get('/servicos', ServicesIndex::class)
    ->middleware('auth')
    ->name('services.index');

Route::get('/mural', MuralIndex::class)
    ->middleware('auth')
    ->name('mural.index');

Route::get('/mural/{article}', ArticleShow::class)
    ->middleware('auth')
    ->name('mural.show');

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

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::middleware('permission:users.view')->group(function () {
        Route::get('/usuarios', UserIndex::class)->name('users.index');
    });

    Route::middleware('permission:services.manage')->group(function () {
        Route::get('/servicos', ServiceLinkIndex::class)->name('services.index');
    });

    Route::middleware('permission:content.view')->group(function () {
        Route::get('/mural', ArticleIndex::class)->name('articles.index');
    });
});

Route::get('/', function () {
    return view('welcome');
});
