<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->unsignedBigInteger('photo_section_id')->nullable()->after('photo_gallery_id');
            $table->unsignedInteger('position')->nullable()->after('alt');
        });

        $galleries = DB::table('photo_galleries')->get();
        foreach ($galleries as $gallery) {
            $sectionId = DB::table('photo_sections')->insertGetId([
                'photo_gallery_id' => $gallery->id,
                'name' => $gallery->name,
                'position' => 1,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('photos')
                ->where('photo_gallery_id', $gallery->id)
                ->orderBy('id')
                ->update([
                    'photo_section_id' => $sectionId,
                    'position' => DB::raw('(
                        SELECT COUNT(*) + 1 FROM photos AS p2 
                        WHERE p2.photo_gallery_id = photos.photo_gallery_id 
                        AND p2.id < photos.id
                    )'),
                ]);
        }

        Schema::table('photos', function (Blueprint $table) {
            $table->unsignedBigInteger('photo_section_id')->nullable(false)->change();
            $table->foreign('photo_section_id')->references('id')->on('photo_sections')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropForeign(['photo_section_id']);
            $table->dropColumn(['photo_section_id', 'position']);
        });
    }
};
