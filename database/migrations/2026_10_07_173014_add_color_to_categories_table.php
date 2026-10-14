<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Paleta usada para colorir as categorias que já existiam antes deste
     * campo existir, uma cor por categoria (cicladas), pra elas não ficarem
     * todas iguais ao padrão assim que a coluna é criada.
     */
    private const PALETTE = ['#7C3AED', '#2563EB', '#059669', '#DB2777', '#D97706', '#4F46E5'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('color')->default('#7C3AED')->after('description');
        });

        Category::orderBy('id')->get()->each(
            fn (Category $category, int $index) => $category->update(['color' => self::PALETTE[$index % count(self::PALETTE)]])
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
