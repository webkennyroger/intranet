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
        // Banner/arte comemorativa de um mês (1-12) exibido na página de
        // Aniversariantes daquele mês — um por mês, no máximo.
        Schema::create('birthday_banners', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('month')->unique();
            $table->string('image_path');
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('birthday_banners');
    }
};
