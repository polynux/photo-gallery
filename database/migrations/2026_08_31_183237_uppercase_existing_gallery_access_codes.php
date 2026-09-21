<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('photo_galleries')) {
            return;
        }

        DB::table('photo_galleries')
            ->whereRaw('access_code != UPPER(access_code)')
            ->update(['access_code' => DB::raw('UPPER(access_code)')]);
    }

    public function down(): void
    {
        // Original casing cannot be restored.
    }
};
