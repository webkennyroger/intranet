<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Adiciona cores configuráveis do "chrome" da UI (cabeçalho de navegação, header e sidebar).
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('nav_header_color')->default('#0C4A34');
            $table->string('header_color')->default('#0C4A34');
            $table->string('sidebar_color')->default('#0C4A34');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['nav_header_color', 'header_color', 'sidebar_color']);
        });
    }
};
