<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Campos personalizados do artigo (lista de blocos extras: texto,
        // botão com link, arquivo para baixar ou imagem), guardados como
        // JSON por serem de forma livre, definida pelo próprio admin.
        Schema::table('articles', function (Blueprint $table) {
            $table->json('custom_fields')->nullable()->after('allow_comments');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('custom_fields');
        });
    }
};
