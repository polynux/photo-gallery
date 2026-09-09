<?php

use App\UniversLayoutPresets;
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
        Schema::table('univers', function (Blueprint $table) {
            $table->string('source_path')->nullable()->after('path');
            $table->decimal('focal_x', 5, 4)->default(0.5)->after('position');
            $table->decimal('focal_y', 5, 4)->default(0.5)->after('focal_x');
            $table->string('processing_status')->default('unprocessed')->after('focal_y');
            $table->json('derivatives')->nullable()->after('processing_status');
        });

        Schema::create('univers_layouts', function (Blueprint $table) {
            $table->id();
            $table->string('mode')->default('preset');
            $table->unsignedInteger('version')->default(1);
            $table->json('layout')->nullable();
            $table->timestamps();
        });

        DB::table('univers')->update(['source_path' => DB::raw('path')]);

        $univers = DB::table('univers')->orderBy('position')->get();
        $classes = UniversLayoutPresets::legacyClasses($univers->count());
        $items = [];

        foreach ($univers as $index => $universItem) {
            $items[] = [
                'univers_id' => $universItem->id,
                'slot' => $index,
                'type' => UniversLayoutPresets::classToType($classes[$index] ?? ''),
            ];
        }

        DB::table('univers_layouts')->insert([
            'mode' => $univers->count() >= 9 && $univers->count() <= 13 ? 'preset' : 'generic',
            'version' => 1,
            'layout' => json_encode([
                'preset' => 'legacy',
                'items' => $items,
            ], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('univers_layouts');

        Schema::table('univers', function (Blueprint $table) {
            $table->dropColumn([
                'source_path',
                'focal_x',
                'focal_y',
                'processing_status',
                'derivatives',
            ]);
        });
    }
};
