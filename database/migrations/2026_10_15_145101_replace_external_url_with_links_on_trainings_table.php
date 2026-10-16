<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->json('links')->nullable()->after('external_url');
        });

        DB::table('trainings')->whereNotNull('external_url')->get(['id', 'external_url'])->each(function ($training) {
            DB::table('trainings')->where('id', $training->id)->update([
                'links' => json_encode([['label' => null, 'url' => $training->external_url]]),
            ]);
        });

        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn('external_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->string('external_url')->nullable()->after('training_category_id');
        });

        DB::table('trainings')->whereNotNull('links')->get(['id', 'links'])->each(function ($training) {
            $links = json_decode($training->links, true);
            DB::table('trainings')->where('id', $training->id)->update([
                'external_url' => $links[0]['url'] ?? null,
            ]);
        });

        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn('links');
        });
    }
};
