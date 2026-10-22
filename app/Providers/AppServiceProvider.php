<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\PhotoAlbum;
use App\Models\Video;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'article' => Article::class,
            'photo_album' => PhotoAlbum::class,
            'video' => Video::class,
        ]);
    }
}
