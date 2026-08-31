<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('projects')
            ->where('image_path', 'like', 'img/portfolio/%.png')
            ->update([
                'image_path' => DB::raw("REPLACE(image_path, '.png', '.webp')"),
            ]);
    }

    public function down(): void
    {
        // The WebP files are the canonical deployed assets. Reverting these
        // paths would point records back to files that no longer exist.
    }
};
