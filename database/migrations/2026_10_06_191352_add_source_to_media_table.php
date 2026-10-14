<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Origem escolhida na hora do upload (Mural, Documentos, Fotos ou
        // Conselho) — o upload só é permitido depois de escolher uma, pra
        // não ter arquivo "sem dono" de categoria nenhuma.
        Schema::table('media', function (Blueprint $table) {
            $table->string('source')->nullable()->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
