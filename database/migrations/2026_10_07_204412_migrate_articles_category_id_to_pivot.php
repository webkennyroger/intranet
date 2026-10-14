<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Copia a categoria única de cada artigo pro novo pivot antes de
        // remover a coluna — artigos agora podem ter várias categorias.
        DB::table('articles')->whereNotNull('category_id')->select('id', 'category_id')->get()
            ->each(function ($article) {
                DB::table('article_category')->insert([
                    'article_id' => $article->id,
                    'category_id' => $article->category_id,
                ]);
            });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
        });

        DB::table('article_category')->orderBy('article_id')->get()
            ->groupBy('article_id')
            ->each(function ($rows, $articleId) {
                DB::table('articles')->where('id', $articleId)->update([
                    'category_id' => $rows->first()->category_id,
                ]);
            });
    }
};
