<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Imagem de capa real (upload) e se o artigo aceita comentários no futuro.
        Schema::table('articles', function (Blueprint $table) {
            $table->string('featured_image')->nullable()->after('cover_label');
            $table->boolean('allow_comments')->default(true)->after('featured_image');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['featured_image', 'allow_comments']);
        });
    }
};
