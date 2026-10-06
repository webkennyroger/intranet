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
        // Configurações visuais globais da intranet (cor primária etc.), registro único.
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('primary_color')->default('#0C4A34');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
