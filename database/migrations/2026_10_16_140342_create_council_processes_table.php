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
        Schema::create('council_processes', function (Blueprint $table) {
            $table->id();
            $table->string('sei_number');
            $table->string('subject');
            $table->string('assigned_to');
            $table->string('interested_party');
            $table->string('external_url')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('council_processes');
    }
};
