<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('photo_galleries')) {
            return;
        }

        DB::table('photo_galleries')
            ->select(['id', 'password'])
            ->orderBy('id')
            ->chunkById(100, function ($galleries): void {
                foreach ($galleries as $gallery) {
                    if (password_get_info($gallery->password)['algoName'] !== 'unknown') {
                        continue;
                    }

                    DB::table('photo_galleries')
                        ->where('id', $gallery->id)
                        ->update([
                            'password' => Hash::make($gallery->password),
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Existing plain-text passwords cannot be restored from their hashed form.
    }
};
