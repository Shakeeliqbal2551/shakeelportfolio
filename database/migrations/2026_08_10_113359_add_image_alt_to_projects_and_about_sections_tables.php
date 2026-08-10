<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('image_alt')->nullable()->after('image_path');
        });

        Schema::table('about_sections', function (Blueprint $table) {
            $table->string('alt_text')->nullable()->after('profile_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('image_alt');
        });

        Schema::table('about_sections', function (Blueprint $table) {
            $table->dropColumn('alt_text');
        });
    }
};
