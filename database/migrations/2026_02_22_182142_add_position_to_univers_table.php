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
        Schema::table('univers', function (Blueprint $table) {
            $table->unsignedInteger('position')->nullable()->after('description');
        });

        // Set sequential positions for existing records
        DB::table('univers')->orderBy('id')->chunk(100, function ($univers) {
            $position = DB::table('univers')
                ->where('id', '<', $univers->first()->id)
                ->count() + 1;

            foreach ($univers as $u) {
                DB::table('univers')
                    ->where('id', $u->id)
                    ->update(['position' => $position++]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('univers', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
